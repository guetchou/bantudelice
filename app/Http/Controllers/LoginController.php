<?php

namespace App\Http\Controllers;

use App\Admin;
use App\User;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login_view()
    {
        if (auth()->check()) {
            // Rediriger selon le type d'utilisateur déjà connecté
            $user = auth()->user();
            if ($user && isset($user->type)) {
                $userType = $user->type;
                switch ($userType) {
                    case 'admin':
                        return redirect()->route('admin.dashboard');
                    case 'restaurant':
                        return redirect()->route('restaurant.dashboard');
                    case 'user':
                    default:
                        return redirect()->route('home');
                }
            }
            // Si pas de type défini, rediriger vers la page d'accueil
            return redirect()->route('home');
        }
        return view('auth.login');
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:191',
            'password' => 'required|string|max:191',
        ]);
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            $alert['type'] = 'danger';
            $alert['heading'] = 'Échec de connexion';
            $alert['message'] = 'Email ou mot de passe invalide';
            return redirect()->back()->with('alert', $alert);
        }
        
        if (!password_verify($request->password, $user->password)) {
            $alert['type'] = 'danger';
            $alert['heading'] = 'Échec de connexion';
            $alert['message'] = 'Email ou mot de passe invalide';
            return redirect()->back()->with('alert', $alert);
        }
        
        // Connexion de l'utilisateur
        auth()->login($user, $request->has('remember'));
        
        // Redirection automatique selon le type de profil
        $userType = auth()->user()->type;
        
        switch ($userType) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'restaurant':
                return redirect()->route('restaurant.dashboard');
            case 'user':
            default:
                return redirect()->route('home');
        }
    }

    public function logout()
    {
        auth()->logout();
        return redirect('login');
    }
}
