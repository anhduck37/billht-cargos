<?php

namespace App\Http\Controllers;

use Google_Client;
use Illuminate\Http\Request;

class GoogleOAuthController extends Controller
{
    protected function getClient()
    {
        $client = new Google_Client();
        $client->setAuthConfig(app_path('Services/google/client_secret.json'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes(['https://www.googleapis.com/auth/drive.file']);
        $client->setRedirectUri(config('app.url') . '/google/oauth2/callback');
        return $client;
    }

    public function start()
    {
        $tokenPath = app_path('Services/google/token.json');
        if (file_exists($tokenPath)) {
            return response("<h3>✅ Google OAuth is already configured.</h3>");
        }

        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return response("<h3>❌ Missing OAuth code. Please try again.</h3>");
        }

        $client = $this->getClient();
        $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

        if (isset($token['error'])) {
            return response('<h3>❌ OAuth Error:</h3><pre>' . json_encode($token, JSON_PRETTY_PRINT) . '</pre>');
        }

        @mkdir(app_path('Services/google'), 0775, true);
        file_put_contents(app_path('Services/google/token.json'), json_encode($client->getAccessToken()));

        return response("<h3>✅ Google OAuth connected successfully!</h3><p>You can now run: <code>php artisan upload_google_drive</code></p>");
    }
}