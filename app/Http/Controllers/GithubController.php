<?php

namespace App\Http\Controllers;

use App\Services\GithubService;
use DomainException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GithubController
{
    public function start(Request $request)
    {
        $intent = $request->query('intent', 'login');
        if (! in_array($intent, ['login', 'link'], true)) {
            return response()->json(['message' => 'Invalid intent.', 'errors' => ['intent' => ['Use login or link.']]], 422);
        }
        if ($intent === 'link' && ! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $request->session()->put('github_flow', ['intent' => $intent, 'user' => $request->user()?->id]);

        return Socialite::driver('github')->scopes(['read:user', 'user:email'])->redirect();
    }

    public function finish(Request $request, GithubService $accounts)
    {
        $session = $request->session();
        $flow = $session->pull('github_flow');
        $state = $session->get('state');
        $returned = $request->query('state');
        if (! $flow || ! is_string($state) || $state === '' || ! is_string($returned) || ! hash_equals($state, $returned)
            || ($flow['intent'] === 'link' && $flow['user'] !== $request->user()?->id)) {
            $session->forget('state');

            return redirect('/login?oauth=invalid_state');
        }

        $link = $flow['intent'] === 'link';
        $onError = $link ? '/settings/account?oauth=' : '/login?oauth=';
        if ($request->has('error') || ! $request->filled('code')) {
            $session->forget('state');

            return redirect($onError.'denied');
        }
        try {
            $profile = Socialite::driver('github')->user();
            $user = $accounts->resolve($profile, $link ? $flow['user'] : null);
        } catch (InvalidStateException) {
            return redirect('/login?oauth=invalid_state');
        } catch (DomainException $error) {
            return redirect($onError.$error->getMessage());
        } catch (GuzzleException|HttpClientException) {
            return redirect($onError.'provider_unavailable');
        }
        if (! $link) {
            Auth::guard('web')->login($user);
            $session->regenerate();
        }

        return redirect('/boards');
    }
}
