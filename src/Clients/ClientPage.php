<?php
declare(strict_types=1);
namespace Sedema\Clients;
use Sedema\Sales\SalesPage;

/** Compatibility facade: both sections use the same commercial interface. */
final class ClientPage
{
    public static function begin(string $title, string $active, array $user): void
    {
        SalesPage::begin($title, $active, $user);
    }
    public static function end(): void { SalesPage::end(); }
    public static function flash(string $type, string $message): void { SalesPage::flash($type, $message); }
    public static function icon(string $name): string { return SalesPage::icon($name); }
}
