<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::query();

        $sortField = request('sort_field', 'id');
        $sortDirection = request('sort_direction', 'asc');

        if (request('name')) {
            $users->where('name', 'like', '%' . request('name') . '%');
        }

        if (request('email')) {
            $users->where('email', 'like', '%' . request('email') . '%');
        }

        $users = $users->orderBy($sortField, $sortDirection)->paginate(10);

        return inertia('User/Index', [
            'users' => UserResource::collection($users),
            'queryParams' => request()->query() ?: null,
            'success' => session('success') ?: null,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('User/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $image = $data['image'] ?? null;
        $data['email_verified_at'] = now();
        $data['password'] = bcrypt($data['password']);

        if ($image) {
            $data['image'] = $image->store('user/' . Str::random(10), 'public');
        }

        User::create($data);

        return redirect()->route('user.index')->with('success', 'User created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return inertia('User/Show', [
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return inertia('User/Edit', [
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $image = $data['image'] ?? null;
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        if ($image) {
            if ($user->image) {
                Storage::deleteDirectory(dirname($user->image));
            }
            $data['image'] = $image->store('user/' . Str::random(10), 'public');
        }

        $user->update($data);

        return redirect()->route('user.index')->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $name = $user->name;

        $user->delete();

        return redirect()->route('user.index')->with('success', "User \"$name\" deleted successfully");
    }
}
