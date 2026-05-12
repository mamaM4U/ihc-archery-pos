<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    public const GATEWAY_MIDTRANS      = 'midtrans';
    public const GATEWAY_XENDIT        = 'xendit';
    public const GATEWAY_BANK_TRANSFER = 'bank_transfer';

    protected $fillable = [
        'default_gateway',
        'bank_transfer_enabled',
        'midtrans_enabled',
        'midtrans_server_key',
        'midtrans_client_key',
        'midtrans_production',
        'xendit_enabled',
        'xendit_secret_key',
        'xendit_public_key',
        'xendit_callback_token',
        'xendit_production',
    ];

    protected $casts = [
        'bank_transfer_enabled' => 'boolean',
        'midtrans_enabled'      => 'boolean',
        'midtrans_production'   => 'boolean',
        'xendit_enabled'        => 'boolean',
        'xendit_production'     => 'boolean',
    ];

    public function enabledGateways(): array
    {
        $gateways = [];

        // Bank Transfer
        if ($this->isBankTransferReady()) {
            $gateways[] = [
                'value'       => self::GATEWAY_BANK_TRANSFER,
                'label'       => 'Transfer Bank',
                'description' => 'Pembayaran manual via transfer bank.',
            ];
        }

        if ($this->isGatewayReady(self::GATEWAY_MIDTRANS)) {
            $gateways[] = [
                'value'       => self::GATEWAY_MIDTRANS,
                'label'       => 'Midtrans',
                'description' => 'Bagikan tautan pembayaran Snap Midtrans ke pelanggan.',
            ];
        }

        if ($this->isGatewayReady(self::GATEWAY_XENDIT)) {
            $gateways[] = [
                'value'       => self::GATEWAY_XENDIT,
                'label'       => 'Xendit',
                'description' => 'Buat invoice otomatis menggunakan Xendit.',
            ];
        }

        return $gateways;
    }

    /**
     * Check if bank transfer is ready (enabled and has active bank accounts)
     */
    public function isBankTransferReady(): bool
    {
        return $this->bank_transfer_enabled && BankAccount::active()->exists();
    }

    public function isGatewayReady(string $gateway): bool
    {
        return match ($gateway) {
            self::GATEWAY_BANK_TRANSFER => $this->isBankTransferReady(),
            self::GATEWAY_MIDTRANS      => $this->midtrans_enabled
            && filled($this->midtrans_server_key)
            && filled($this->midtrans_client_key),
            self::GATEWAY_XENDIT        => $this->xendit_enabled
            && filled($this->xendit_secret_key)
            && filled($this->xendit_public_key),
            default                     => false,
        };
    }

    public function midtransConfig(): array
    {
        return [
            'enabled'       => $this->isGatewayReady(self::GATEWAY_MIDTRANS),
            'server_key'    => $this->midtrans_server_key,
            'client_key'    => $this->midtrans_client_key,
            'is_production' => $this->midtrans_production,
        ];
    }

    public function xenditConfig(): array
    {
        return [
            'enabled'       => $this->isGatewayReady(self::GATEWAY_XENDIT),
            'secret_key'    => $this->xendit_secret_key,
            'public_key'    => $this->xendit_public_key,
            'callback_token' => $this->xendit_callback_token,
            'is_production' => $this->xendit_production,
        ];
    }
}
