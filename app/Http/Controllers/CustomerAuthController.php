<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerAuthController extends Controller
{
    // Menampilkan halaman login
    public function login()
    {
        return view('web.customer.login', [
            'title' => 'Login'
        ]);
    }

    // Menampilkan halaman register
    public function register()
    {
        return view('web.customer.register', [
            'title' => 'Register'
        ]);
    }

    // Proses registrasi customer baru
    public function store_register(Request $request)
    {
        // Validasi input registrasi
        $validasi = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:customers,email',
            'password' => 'required|confirmed|min:8',
            'password_confirmation' => 'required'
        ]);

        // Jika validasi gagal, kembali ke form registrasi dengan pesan
        if ($validasi->fails()) {
            return redirect()->back()
                ->with('errorMessage', 'Validasi error, silahkan cek kembali data anda')
                ->withErrors($validasi)
                ->withInput();
        }

        // Simpan customer baru ke database
        $customer = new Customer;
        $customer->name = $request->name;
        $customer->email = $request->email;
        $customer->password = Hash::make($request->password);
        $customer->save();

        // Redirect ke halaman login dengan pesan sukses
        return redirect()->route('customer.login')
            ->with('successMessage', 'Registrasi Berhasil');
    }

    // Proses login customer
    public function store_login(Request $request)
    {
        // Ambil data email dan password
        $credentials = $request->only('email', 'password');

        // Validasi input login
        $validasi = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Jika validasi gagal, kembalikan ke halaman login
        if ($validasi->fails()) {
            return redirect()->back()
                ->with('errorMessage', 'Validasi error, silahkan cek kembali data anda')
                ->withErrors($validasi)
                ->withInput();
        }

        // Cari customer berdasarkan email
        $customer = Customer::where('email', $credentials['email'])->first();

        // Cek apakah customer ditemukan dan password valid
        if ($customer && Hash::check($credentials['password'], $customer->password)) {
            // Login customer menggunakan guard 'customer'
            Auth::guard('customer')->login($customer);

            // Redirect ke halaman home dengan pesan sukses
            return redirect()->route('home')
                ->with('successMessage', 'Login berhasil');
        } else {
            // Jika login gagal, kembali dengan pesan error
            return redirect()->back()
                ->with('errorMessage', 'Email atau password salah')
                ->withInput();
        }
    }

    // Proses logout customer
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        return redirect()->route('customer.login')
            ->with('successMessage', 'Anda telah berhasil logout');
    }
}
