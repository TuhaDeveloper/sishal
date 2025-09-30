<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $pageTitle = 'My Profile';
        $orders = Order::where('user_id',auth()->id())->paginate(5);
        $services = Service::where('user_id',auth()->id())->paginate(5);

        return view('ecommerce.myprofile', [
            'user' => $request->user(),
        ], compact('pageTitle', 'orders', 'services'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        
        try {
            $user = $request->user();
            $user->fill($request->only(['first_name', 'last_name', 'email']));

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            $user->save();

            // Update or create customer info
            $customerData = [
                'phone' => $request->input('phone'),
                'address_1' => $request->input('address_1'),
                'address_2' => $request->input('address_2'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'zip_code' => $request->input('zip_code'),
                'country' => $request->input('country'),
                'user_id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
            ];
            if ($user->customer) {
                $user->customer->update($customerData);
            } else {
                \App\Models\Customer::create($customerData);
            }

            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        } catch (\Exception $e) {
            Log::error('Profile update error: ' . $e->getMessage(), ['exception' => $e]);
            return Redirect::route('profile.edit')->with('error', 'An error occurred while updating your profile. Please try again.');
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
