<?php
/**
 * Created by PhpStorm.
 * User: thanhtu
 * Date: 12/25/2016
 * Time: 2:49 PM
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\models\Posts;
use App\models\User;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function login(Request $request)
    {

        $validation = Validator::make($request->input(), [
            'user_login' => 'required',
            'user_pass' => 'required'
        ], [
            'user_login.required' => 'Vui lòng nhập tên đăng nhập.',
            'user_pass.required' => 'Vui lòng nhập mật khẩu'
        ]);

        if ($validation->passes()) {
            $model = new User();
            $result = $model->login($request);
        } else {
            $result = [
                'status' => 'fail',
                'data' => $validation->messages()
            ];
        }

        return response()->json($result)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function register(Request $request)
    {
        $validation = Validator::make($request->input(), [
            'user_login' => 'required',
            'user_pass' => 'required|min:3',
            'name' => 'required',
            'gender' => 'required',
            'city_id' => 'required',
            'phone' => 'required'
        ], [
            'user_login.required' => 'Vui lòng nhập tên đăng nhập.',
            'user_pass.required' => 'Vui lòng nhập mật khẩu.',
            'user_pass.min' => 'Độ dài mật khẩu phải lớn hơn :min kí tự.',
            'name.required' => 'Vui lòng nhập họ tên.',
            'gender.required' => 'Vui lòng chọn giới tính.',
            'city_id.required' => 'Vui lòng chọn tỉnh/thành phố',
            'phone.required' => 'Vui lòng nhập số điện thoại'
        ]);

        if ($validation->passes()) {
            $model = new User();
            $result = $model->register($request);
        } else {
            $result = [
                'status' => 'fail',
                'data' => $validation->messages()
            ];
        }

        return response()->json($result)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function facebookRegister(Request $request)
    {
        $validation = Validator::make($request->input(), [
            'email' => 'required',
            'name' => 'required'
        ]);

        if ($validation->passes()) {
            $model = new User();
            $result = $model->facebookRegister($request);
        } else {
            $result = [
                'status' => 'fail',
                'data' => ['Đã xảy ra lỗi, vui lòng thử lại.']
            ];
        }

        return response()->json($result)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function changePassword(Request $request)
    {
        if (!empty($request->input('user_id'))) {
            $validation = Validator::make($request->input(), [
                'new_pw' => 'required|confirmed|min:3',
                'new_pw_confirmation' => 'required'
            ], [
                'new_pw.required' => 'Vui lòng nhập mật khẩu mới.',
                'new_pw_confirmation.required' => 'Vui lòng nhập lại mật khẩu.',
                'new_pw.confirmed' => 'Mật khẩu không trùng khớp.',
                'new_pw.min' => 'Độ dài mật khẩu phải lớn hơn :min kí tự.'
            ]);

            if ($validation->passes()) {
                $model = new User();
                $result = $model->changePassword($request);
            } else {
                $result = [
                    'status' => 'fail',
                    'data' => $validation->messages()
                ];
            }
        } else {
            $result = array(
                'status' => 'error',
                'message' => 'Đã xảy ra lỗi, vui lòng thử lại',
            );
        }

        return response()->json($result)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function getToken(Request $request){
        $user = new User();
        return $user->getToken($request);
    }
}