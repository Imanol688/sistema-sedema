<?php
declare(strict_types=1);

namespace Sedema\Clients;

use PDOException;

final class ClientService
{
    public const TYPES = ['PARTICULAR', 'ALBAÑIL', 'EMPRESA', 'MAYORISTA', 'CONTRATISTA'];

    public function __construct(private readonly ClientRepository $repository)
    {
    }

    /** @param array<string,mixed> $input */
    public function save(array $input): int
    {
        $id = max(0, (int) ($input['idCliente'] ?? 0));
        $name = $this->required($input['nombre'] ?? '', 'Ingresá el nombre del cliente.', 100);
        $surname = $this->required($input['apellido'] ?? '', 'Ingresá el apellido del cliente.', 100);
        $document = $this->required($input['cuitDNI'] ?? '', 'Ingresá el DNI o CUIT del cliente.', 20);
        $type = mb_strtoupper(trim((string) ($input['tipoCliente'] ?? '')));
        if (!in_array($type, self::TYPES, true)) {
            throw new ClientException('Seleccioná un tipo de cliente válido.');
        }
        $businessName = mb_substr(trim((string) ($input['razonSocial'] ?? '')), 0, 150);
        $phone = mb_substr(trim((string) ($input['telefono'] ?? '')), 0, 50);
        $address = $this->required($input['direccion'] ?? '', 'Ingresá la dirección del cliente.', 255);
        $city = $this->required($input['localidad'] ?? '', 'Ingresá la localidad del cliente.', 100);

        if ($this->repository->documentExists($document, $id)) {
            throw new ClientException('Ya existe un cliente registrado con ese DNI o CUIT.');
        }

        $data = [
            'nombre' => $name,
            'apellido' => $surname,
            'cuitDNI' => $document,
            'tipoCliente' => $type,
            'razonSocial' => $businessName !== '' ? $businessName : null,
            'telefono' => $phone !== '' ? $phone : null,
            'direccion' => $address,
            'localidad' => $city,
        ];

        try {
            if ($id > 0) {
                if (!$this->repository->client($id)) {
                    throw new ClientException('El cliente indicado no existe.');
                }
                $this->repository->update($id, $data);
                return $id;
            }
            return $this->repository->insert($data);
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                throw new ClientException('No se pudo guardar el cliente porque el DNI/CUIT ya está registrado.');
            }
            throw $error;
        }
    }

    public function setActive(int $id, bool $active): void
    {
        if ($id <= 0 || !$this->repository->client($id)) {
            throw new ClientException('El cliente indicado no existe.');
        }
        $this->repository->setActive($id, $active);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'PARTICULAR' => 'Particular',
            'ALBAÑIL' => 'Albañil',
            'EMPRESA' => 'Empresa',
            'MAYORISTA' => 'Mayorista',
            'CONTRATISTA' => 'Contratista',
            default => $type,
        };
    }

    private function required(mixed $value, string $message, int $max): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            throw new ClientException($message);
        }
        return mb_substr($text, 0, $max);
    }
}
