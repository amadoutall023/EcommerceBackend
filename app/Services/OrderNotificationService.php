<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderNotificationService
{
    public function sendNewOrderNotification(array $order, User $customer): void
    {
        $recipient = (string) config('services.order_notifications.to', '');

        if ($recipient === '') {
            return;
        }

        $subject = sprintf('Nouvelle commande #%d', $order['id']);
        $lines = [
            'Une nouvelle commande a ete enregistree sur TaTrend.',
            '',
            sprintf('Commande: #%d', $order['id']),
            sprintf('Client: %s', $customer->name),
            sprintf('Telephone: %s', $customer->phone ?? 'Non renseigne'),
            sprintf('Email: %s', $this->displayCustomerEmail($customer)),
            sprintf('Montant total: %.2f', (float) ($order['total_amount'] ?? 0)),
            sprintf('Statut: %s', $order['status'] ?? 'pending'),
            sprintf('Date: %s', $order['created_at'] ?? now()->toDateTimeString()),
            '',
            'Articles:',
            $this->formatItems($order['items'] ?? []),
        ];

        try {
            Mail::raw(implode("\n", $lines), function ($message) use ($recipient, $subject) {
                $message->to($recipient)->subject($subject);
            });
        } catch (\Throwable $exception) {
            Log::warning('Order notification email failed.', [
                'order_id' => $order['id'] ?? null,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function displayCustomerEmail(User $customer): string
    {
        if (!$customer->email || str_ends_with($customer->email, '@tatrend.local')) {
            return 'Non renseigne';
        }

        return $customer->email;
    }

    private function formatItems(array $items): string
    {
        if ($items === []) {
            return '- Aucun article';
        }

        return implode("\n", array_map(function (array $item): string {
            $variantParts = array_filter([
                isset($item['selected_size']) && $item['selected_size'] !== null ? 'taille '.$item['selected_size'] : null,
                isset($item['selected_color']) && $item['selected_color'] !== null ? 'couleur '.$item['selected_color'] : null,
            ]);

            $variantSuffix = $variantParts === [] ? '' : ' ('.implode(', ', $variantParts).')';

            return sprintf(
                '- %s x%d%s - %.2f',
                $item['product_name'] ?? 'Produit',
                (int) ($item['quantity'] ?? 0),
                $variantSuffix,
                (float) ($item['subtotal'] ?? 0)
            );
        }, $items));
    }
}
