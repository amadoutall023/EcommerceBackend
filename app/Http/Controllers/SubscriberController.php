<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriberController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->input('email');

        // Check if already subscribed
        $existing = DB::table('subscribers')->where('email', $email)->first();

        if ($existing) {
            if (!$existing->is_active) {
                // Reactivate subscription
                DB::table('subscribers')
                    ->where('email', $email)
                    ->update(['is_active' => true, 'updated_at' => now()]);

                return response()->json([
                    'message' => 'Vous êtes de retour ! Votre abonnement a été réactivé.'
                ]);
            }

            return response()->json([
                'message' => 'Vous êtes déjà inscrit à notre newsletter.'
            ], 400);
        }

        // Create new subscription
        DB::table('subscribers')->insert([
            'email' => $email,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Merci ! Vous êtes maintenant inscrit à notre newsletter.'
        ]);
    }

    public function index()
    {
        $subscribers = DB::table('subscribers')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $subscribers]);
    }
}
