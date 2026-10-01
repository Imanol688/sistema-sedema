<?php
declare(strict_types=1);

namespace Sedema\Access;

final class UserAccessService
{
    private const ROLES = ['CAJA','ALMACEN','FINANZAS','SISTEMAS','VENDEDOR','PROVEEDOR','DEPOSITO','LOGISTICA'];
    private const MODULES = ['ventas','clientes','inventario','proveedores','pagos','logistica'];

    public function __construct(private readonly UserAccessRepository $repository, private readonly CredentialMailer $mailer) {}

    /** @param array<string,mixed> $input */
    public function createAccount(array $input, int $actorUserId): int
    {
        $name=$this->required($input['nombre']??'','Ingresá el nombre.',100);
        $surname=$this->required($input['apellido']??'','Ingresá el apellido.',100);
        $dni=$this->required($input['dni']??'','Ingresá el DNI.',20);
        $email=$this->validatedEmail($input['email']??'');
        if ($this->repository->emailExists($email)) throw new AccessException('Ese correo ya está vinculado a otra cuenta.');
        $role=mb_strtoupper(trim((string)($input['role']??'')));
        if (!in_array($role,self::ROLES,true)) throw new AccessException('Seleccioná un rol de acceso válido.');
        $permissions=$this->normalizePermissions($input['permissions']??[]);
        $temporaryPassword=$this->temporaryPassword();
        try {
            return $this->repository->transaction(function() use($name,$surname,$dni,$email,$temporaryPassword,$role,$permissions,$actorUserId): int {
                $userId=$this->repository->createAccount(['nombre'=>$name,'apellido'=>$surname,'dni'=>$dni],$email,password_hash($temporaryPassword,PASSWORD_DEFAULT),$role,$permissions,$actorUserId);
                $this->mailer->sendTemporaryCredential($email,$name.' '.$surname,$temporaryPassword);
                $this->repository->markCredentialSent($userId,$actorUserId);
                return $userId;
            });
        } catch (AccessException $e) { throw $e; }
        catch (\Throwable $e) { throw new AccessException('No se pudo crear la cuenta porque falló el envío del correo. Revisá MAIL_TRANSPORT y las credenciales SMTP de Gmail en .env.'); }
    }

    /** @param array<string,mixed> $input */
    public function updateAccess(array $input, int $actorUserId): void
    {
        $userId=max(0,(int)($input['idUsuario']??0));
        $account=$this->repository->account($userId);
        if(!$account) throw new AccessException('La cuenta indicada no existe.');
        if($userId===$actorUserId && (string)($input['enabled']??'0')!=='1') throw new AccessException('No podés deshabilitar tu propia cuenta mientras estás usando el sistema.');
        $email=$this->validatedEmail($input['email']??'');
        if($this->repository->emailExists($email,$userId)) throw new AccessException('Ese correo ya está vinculado a otra cuenta.');
        $role=mb_strtoupper(trim((string)($input['role']??'')));
        if(!in_array($role,array_merge(['ADMINISTRADOR'],self::ROLES),true)) throw new AccessException('Seleccioná un rol válido.');
        $this->repository->updateAccess($userId,$email,$role,$this->normalizePermissions($input['permissions']??[]),(string)($input['enabled']??'0')==='1',$actorUserId);
    }

    public function deleteAccount(int $userId, int $actorUserId): void
    {
        if($userId<=0) throw new AccessException('La cuenta indicada no es válida.');
        if($userId===$actorUserId) throw new AccessException('No podés eliminar tu propia cuenta mientras estás usando el sistema.');
        $this->repository->deleteAccount($userId,$actorUserId);
    }

    /** @param list<int> $userIds */
    public function deleteAccounts(array $userIds, int $actorUserId): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn(int $id): bool => $id > 0)));
        if (!$ids) {
            throw new AccessException('Seleccioná al menos una cuenta para eliminar.');
        }
        if (in_array($actorUserId, $ids, true)) {
            throw new AccessException('No podés eliminar tu propia cuenta mientras estás usando el sistema.');
        }
        return $this->repository->transaction(function () use ($ids, $actorUserId): int {
            foreach ($ids as $id) {
                $this->repository->deleteAccount($id, $actorUserId);
            }
            return count($ids);
        });
    }

    public function resendTemporaryCredential(int $userId, int $actorUserId): void
    {
        $account=$this->repository->account($userId);
        if(!$account) throw new AccessException('La cuenta indicada no existe.');
        $email=trim((string)($account['email']??''));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new AccessException('La cuenta no tiene un correo electrónico válido.');
        $temporaryPassword=$this->temporaryPassword();
        try {
            $this->repository->transaction(function() use($userId,$actorUserId,$account,$email,$temporaryPassword): void {
                $this->repository->replaceTemporaryPassword($userId,password_hash($temporaryPassword,PASSWORD_DEFAULT),$actorUserId);
                $this->mailer->sendTemporaryCredential($email,trim((string)$account['nombre'].' '.(string)$account['apellido']),$temporaryPassword);
                $this->repository->markCredentialSent($userId,$actorUserId);
            });
        } catch (AccessException $e) { throw $e; }
        catch (\Throwable $e) { throw new AccessException('No se pudo reenviar la credencial. Revisá la configuración SMTP de Gmail en el archivo .env.'); }
    }

    /** @param array<string,mixed> $input */
    public function completeFirstAccess(array $input, int $userId, ?array $file): void
    {
        $account=$this->repository->account($userId);
        if(!$account || (int)$account['mustChangePassword']!==1) throw new AccessException('Esta cuenta no requiere configuración inicial.');
        $username=mb_strtolower(trim((string)($input['username']??'')));
        if(!preg_match('/^[a-z0-9._-]{4,30}$/',$username)) throw new AccessException('El usuario debe tener entre 4 y 30 caracteres y usar solo letras, números, punto, guion o guion bajo.');
        if($this->repository->usernameExists($username,$userId)) throw new AccessException('Ese nombre de usuario ya está en uso.');
        $password=(string)($input['password']??''); $confirmation=(string)($input['password_confirmation']??'');
        if(strlen($password)<10 || !preg_match('/[A-Za-z]/',$password) || !preg_match('/\d/',$password)) throw new AccessException('La nueva contraseña debe tener al menos 10 caracteres e incluir letras y números.');
        if(!hash_equals($password,$confirmation)) throw new AccessException('Las contraseñas nuevas no coinciden.');
        if(password_verify($password,(string)$account['passwordHash'])) throw new AccessException('La contraseña definitiva debe ser distinta de la contraseña temporal.');
        $this->repository->completeInitialSetup($userId,$username,password_hash($password,PASSWORD_DEFAULT),$this->storeProfilePhoto($file));
    }

    private function validatedEmail(mixed $value): string
    {
        $email=mb_strtolower(trim((string)$value));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($email)>150) throw new AccessException('Ingresá un correo electrónico válido.');
        return $email;
    }

    /** @return list<string> */
    private function normalizePermissions(mixed $input): array
    {
        $values=is_array($input)?$input:[]; $allowed=[];
        foreach($values as $value){ $module=trim((string)$value); if(!in_array($module,self::MODULES,true)) continue; $allowed[]=$module.'.*'; if($module==='inventario') $allowed[]='inventory.*'; if($module==='ventas') $allowed[]='sales.*'; if($module==='pagos') $allowed[]='payments.*'; }
        return array_values(array_unique($allowed));
    }

    private function temporaryPassword(): string
    {
        $alphabet='ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'; $password='';
        for($i=0;$i<8;$i++) $password.=$alphabet[random_int(0,strlen($alphabet)-1)];
        return $password;
    }

    private function storeProfilePhoto(?array $file): ?string
    {
        if(!$file || (int)($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
        if((int)($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK) throw new AccessException('No se pudo cargar la foto de perfil.');
        if((int)($file['size']??0)>2*1024*1024) throw new AccessException('La foto de perfil no puede superar los 2 MB.');
        $tmp=(string)($file['tmp_name']??''); $mime=is_file($tmp)?(new \finfo(FILEINFO_MIME_TYPE))->file($tmp):false;
        $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if(!is_string($mime)||!isset($extensions[$mime])) throw new AccessException('La foto debe ser JPG, PNG o WebP.');
        $directory=BASE_PATH.'/public/uploads/profiles';
        if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory)) throw new AccessException('No se pudo preparar el directorio de fotos de perfil.');
        $filename=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
        if(!move_uploaded_file($tmp,$directory.'/'.$filename)) throw new AccessException('No se pudo guardar la foto de perfil.');
        return 'uploads/profiles/'.$filename;
    }

    private function required(mixed $value,string $message,int $max): string
    { $text=trim((string)$value); if($text==='') throw new AccessException($message); return mb_substr($text,0,$max); }
}
