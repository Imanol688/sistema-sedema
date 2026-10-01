<?php
declare(strict_types=1);

namespace Sedema\Profile;

final class ProfileService
{
    public function __construct(private readonly ProfileRepository $repository) {}

    /** @param array<string,mixed> $input @param array<string,mixed>|null $photo */
    public function update(array $input, int $userId, ?array $photo): array
    {
        $current = $this->repository->profile($userId);
        if (!$current) {
            throw new ProfileException('No se pudo encontrar el perfil de la cuenta.');
        }

        $name = trim((string) ($input['nombre'] ?? ''));
        $lastName = trim((string) ($input['apellido'] ?? ''));
        $username = trim((string) ($input['username'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $phone = trim((string) ($input['telefono'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100 || $lastName === '' || mb_strlen($lastName) > 100) {
            throw new ProfileException('Ingresá nombre y apellido válidos.');
        }
        if (!preg_match('/^[A-Za-z0-9._-]{4,30}$/', $username)) {
            throw new ProfileException('El usuario debe tener entre 4 y 30 caracteres y usar solo letras, números, punto, guion o guion bajo.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            throw new ProfileException('Ingresá un correo electrónico válido.');
        }
        if (mb_strlen($phone) > 50) {
            throw new ProfileException('El teléfono es demasiado largo.');
        }
        if ($this->repository->usernameExists($username, $userId)) {
            throw new ProfileException('Ese nombre de usuario ya está siendo utilizado.');
        }
        if ($this->repository->emailExists($email, $userId)) {
            throw new ProfileException('Ese correo electrónico ya está asociado a otra cuenta.');
        }

        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmation = (string) ($input['new_password_confirmation'] ?? '');
        $changePassword = $currentPassword !== '' || $newPassword !== '' || $confirmation !== '';

        if ($changePassword) {
            if (!password_verify($currentPassword, (string) $current['passwordHash'])) {
                throw new ProfileException('La contraseña actual no es correcta.');
            }
            if (strlen($newPassword) < 10 || !preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
                throw new ProfileException('La nueva contraseña debe tener al menos 10 caracteres, incluyendo letras y números.');
            }
            if ($newPassword !== $confirmation) {
                throw new ProfileException('Las nuevas contraseñas no coinciden.');
            }
            if (password_verify($newPassword, (string) $current['passwordHash'])) {
                throw new ProfileException('La nueva contraseña debe ser diferente de la contraseña actual.');
            }
        }

        $photoPath = $this->storePhoto($photo, $userId);
        $this->repository->updateProfile(
            $userId,
            (int) $current['idEmpleado'],
            $name,
            $lastName,
            $username,
            $email,
            $phone !== '' ? $phone : null,
            $photoPath
        );

        $newAuthVersion = (int) $current['authVersion'];
        if ($changePassword) {
            $newAuthVersion = $this->repository->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
        }

        $updated = $this->repository->profile($userId);
        if (!$updated) {
            throw new ProfileException('No se pudo recargar el perfil actualizado.');
        }

        if (isset($_SESSION['auth']) && is_array($_SESSION['auth'])) {
            $_SESSION['auth']['username'] = (string) $updated['username'];
            $_SESSION['auth']['name'] = trim((string) $updated['nombre'] . ' ' . (string) $updated['apellido']);
            $_SESSION['auth']['profile_photo'] = (string) ($updated['profilePhoto'] ?? '');
            $_SESSION['auth']['auth_version'] = $newAuthVersion;
        }

        return $updated;
    }

    /** @param array<string,mixed>|null $photo */
    private function storePhoto(?array $photo, int $userId): ?string
    {
        if (!$photo || (int) ($photo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($photo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new ProfileException('No se pudo subir la foto de perfil.');
        }
        if ((int) ($photo['size'] ?? 0) <= 0 || (int) $photo['size'] > 2 * 1024 * 1024) {
            throw new ProfileException('La foto debe pesar como máximo 2 MB.');
        }
        $tmp = (string) ($photo['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new ProfileException('El archivo de imagen recibido no es válido.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            throw new ProfileException('La foto debe ser JPG, PNG o WebP.');
        }
        $directory = BASE_PATH . '/public/uploads/profiles';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new ProfileException('No se pudo preparar la carpeta de fotos de perfil.');
        }
        $filename = sprintf('user_%d_%s.%s', $userId, bin2hex(random_bytes(6)), $extensions[$mime]);
        if (!move_uploaded_file($tmp, $directory . '/' . $filename)) {
            throw new ProfileException('No se pudo guardar la foto de perfil.');
        }
        return 'uploads/profiles/' . $filename;
    }
}
