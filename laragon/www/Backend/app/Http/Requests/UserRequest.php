<?php

namespace App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if (request()->routeIs('user.login')) {
            return [
                'email' => 'required|string|email|max:255',
                'password' => 'required|min:8',
            ];
        } else if(request()->routeIs('user.signup')) {
            return [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users|max:255',
                'password' => 'required|string|min:8',
                'confirm_password' => 'required|string|min:8',
            ];
            
        } else if (request()->routeIs('user.store')) {
            return [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users|max:255',
                'password' => 'required|string|min:8',
                'confirm_password' => 'required|string|min:8',
                'address' => 'required|string',
                'phone_number' => 'required|string',
            ];
        } else if (request()->routeIs('user.update')) {
            return [
                'name' => 'required|string|max:255'
            ];
        } else if (request()->routeIs('user.email')) {
            return [
                'email' => 'required|string|email|max:255',
            ];
        } else if (request()->routeIs('user.password')) {
            return [
                'password' => 'required|confirmed|min:8',
            ];
        } else if (request()->routeIs('user.image') || request()->routeIs('profile.image') || request()->routeIs('ocr.image')) {
            return [
                'profile_image' => 'required|image|mimes:jpg,bmp,png|max:2048',
            ];
        }

        return [];
    }
}
