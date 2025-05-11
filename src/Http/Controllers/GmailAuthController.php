<?php

namespace PartridgeRocks\GmailClient\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PartridgeRocks\GmailClient\GmailClient;

class GmailAuthController extends Controller
{
    /**
     * Redirect to Google OAuth.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(GmailClient $client)
    {
        $authUrl = $client->getAuthorizationUrl(
            config('gmail-client.redirect_uri'),
            config('gmail-client.scopes')
        );

        return redirect($authUrl);
    }

    /**
     * Handle callback from Google OAuth.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request, GmailClient $client)
    {
        if ($request->has('error')) {
            return redirect()->route('gmail.error')
                ->with('error', $request->get('error'));
        }

        $code = $request->get('code');

        try {
            // Exchange authorization code for an access token
            $token = $client->exchangeCode($code, config('gmail-client.redirect_uri'));

            // Store token in session (for demo purposes)
            // In a real application, you would store this token securely
            // and associate it with the authenticated user
            session([
                'gmail_access_token' => $token['access_token'],
                'gmail_refresh_token' => $token['refresh_token'] ?? null,
                'gmail_token_expires' => now()->addSeconds($token['expires_in']),
            ]);

            return redirect()->route('gmail.success');

        } catch (\Exception $e) {
            return redirect()->route('gmail.error')
                ->with('error', $e->getMessage());
        }
    }
}
