<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return view('dashboard.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dashboard.users.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'no_handphone' => 'nullable|numeric',
            'role' => 'required|in:admin,penjaga',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = new User();
        $user->name = $request->input('name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');
        $user->password = $request->input('password');
        $user->no_handphone = $request->input('no_handphone');
        $user->role = $request->input('role');
        $user->save();

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            return view('dashboard.users.show', compact('user'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $user = User::findOrFail($id);
            return view('dashboard.users.edit', compact('user'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'no_handphone' => 'nullable|numeric',
            'role' => 'required|in:admin,penjaga',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $user = User::findOrFail($id);
            $user->name = $request->input('name');
            $user->last_name = $request->input('last_name');
            $user->email = $request->input('email');
            if ($request->filled('password')) {
                $user->password = $request->input('password');
            }
            if ($request->filled('no_handphone')) {
                $user->no_handphone = $request->input('no_handphone');
            }
            $user->role = $request->input('role');
            $user->save();

            return redirect()->route('users.index')->with('success', 'User updated successfully.');
        } catch (ModelNotFoundException $e) {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return redirect()->route('users.index')->with('success', 'User deleted successfully.');
        } catch (ModelNotFoundException $e) {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }
    }
}
