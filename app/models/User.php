<?php
/**
 * Created by PhpStorm.
 * User: thanhtu
 * Date: 12/25/2016
 * Time: 2:53 PM
 */
namespace App\models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Helper\PasswordHash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;


class User extends Model
{
    protected $prefix;
    protected $table_user;
    protected $table_user_info;
    protected $table_usermeta;
    protected $table_user_family;
    protected $table_district;
    protected $table_city;
    protected $table_posts;
    protected $table_statistics_user;

    public function __construct()
    {
        parent::__construct();
        $this->prefix = "wp_";
        $this->table_user = $this->prefix . "users";
        $this->table_usermeta = $this->prefix . "usermeta";
        $this->table_posts = $this->prefix . "posts";
    }


    /**
     * Lấy thông tin user theo ID
     * @param $user_id
     * @return mixed
     */
    public function getUserInfo($user_id)
    {
        $cacheId = __METHOD__ . $user_id;
        $result = Cache::remember($cacheId, env('CACHE_TIME'), function () use ($user_id) {

            // table users
            $user = DB::table($this->table_user)->where('ID', $user_id)->first();
            if (!empty($user)) {

                //merge user + user_info + user_meta
                $result = (object)$user;
                $data = [
                    'ID' => $result->ID,
                    'display_name' => $result->display_name,
                    'name' => $result->display_name,
                    'email' => $result->user_email,
                    'ip' => Request::ip(),
                ];

                return (object)$data;
            } else {
                return false;
            }
        });

        return $result;
    }


    /**
     * Đăng nhập
     *
     * @param $request
     *
     * @return array
     */
    public function login($request)
    {
        $data = $request->input();
        $hasher = new PasswordHash(8, true);

        $user = DB::table($this->table_user)->where('user_login', strtolower(trim($data['user_login'])))->first();

        if (empty($user)) {
            $result = array(
                'status' => 'fail',
                'data' => 'Tài khoản hoặc mật khẩu không đúng',
            );
        } else {
            if ($hasher->CheckPassword(trim($data['user_pass']), $user->user_pass)) {
                $user = $this->getUserInfo($user->ID);

                $result = array(
                    'status' => 'success',
                    'data' => $user
                );
            } else {
                $result = array(
                    'status' => 'fail',
                    'data' => 'Tài khoản hoặc mật khẩu không đúng',
                );
            }
        }

        return $result;
    }


    /**
     * Đăng ký
     *
     * @param $request
     *
     * @return array
     */
    public function register($request)
    {
        $data = $request->input();

        //trim
        $data['user_login'] = strtolower(trim($data['user_login']));
        $data['user_pass'] = trim($data['user_pass']);
        $data['name'] = trim(valueOrNull($data['name'], ''));

        $is_exist = DB::table($this->table_user)
            ->where('user_email', $data['user_login'])
            ->orWhere('user_login', $data['user_login'])
            ->first();
        if (empty($is_exist)) {
            //register transaction
            $result = DB::transaction(function () use ($data) {
                $cls_password = new PasswordHash(8, true);
                $password = $cls_password->HashPassword($data['user_pass']);
                $user_nicename_arr = explode('@', $data['user_login']);
                $user_nicename = sanitize_user($user_nicename_arr[0], true);
                DB::table($this->table_user)->insert([
                    'user_login' => $data['user_login'],
                    'user_pass' => $password,
                    'user_email' => $data['user_login'],
                    'user_registered' => Carbon::now(),
                    'user_logged' => Carbon::now(),
                    'display_name' => valueOrNull($data['name'], 'User'),
                    'user_nicename' => $user_nicename
                ]);

                $user_id = valueOrNull(DB::table($this->table_user)
                    ->where('user_login', $data['user_login'])
                    ->first(['ID'])->ID, 0);

                if (!empty($user_id)) {
                    //usermeta add user capabilities subscriber
                    DB::table($this->table_usermeta)
                        ->insert([
                            [
                                'user_id' => $user_id,
                                'meta_key' => 'mb_capabilities',
                                'meta_value' => 'a:1:{s:10:"subscriber";b:1;}'
                            ],
                            [
                                'user_id' => $user_id,
                                'meta_key' => 'mb_user_level',
                                'meta_value' => '0'
                            ]
                        ]);

                    $rs = [
                        'status' => 'success',
                        'data' => $this->getUserInfo($user_id)
                    ];
                } else {
                    $rs = [
                        'status' => 'error',
                        'message' => 'Đã xảy ra lỗi, vui lòng thử lại.'
                    ];
                }

                return $rs;
            });
        } else {
            $result = [
                'status' => 'fail',
                'data' => ['Tài khoản đã tồn tại.']
            ];
        }

        return $result;
    }


    /**
     * Đăng ký bằng tài khoản facebook
     *
     * @param $request
     * @return array
     */
    public function facebookRegister($request)
    {
        $data = $request->input();

        $data['email'] = strtolower(trim($data['email']));
        $data['name'] = trim($data['name']);

        $is_exist = DB::table($this->table_user)
            ->where('user_email', $data['email'])
            ->orWhere('user_login', $data['email'])
            ->first();
        if (empty($is_exist)) {
            //register transaction
            $result = DB::transaction(function () use ($data) {
                $cls_password = new PasswordHash(8, true);
                $password = $cls_password->HashPassword($data['email']);
                $user_nicename_arr = explode('@', $data['email']);
                $user_nicename = sanitize_user($user_nicename_arr[0], true);
                DB::table($this->table_user)->insert([
                    'user_login' => $data['email'],
                    'user_pass' => $password,
                    'user_email' => $data['email'],
                    'user_registered' => Carbon::now(),
                    'user_logged' => Carbon::now(),
                    'display_name' => valueOrNull($data['name'], 'User'),
                    'user_nicename' => $user_nicename
                ]);

                $user_id = valueOrNull(DB::table($this->table_user)
                    ->where('user_login', $data['email'])
                    ->first(['ID'])->ID, 0);

                if (!empty($user_id)) {
                    //usermeta add user capabilities subscriber
                    DB::table($this->table_usermeta)
                        ->insert([
                            [
                                'user_id' => $user_id,
                                'meta_key' => 'mb_capabilities',
                                'meta_value' => 'a:1:{s:10:"subscriber";b:1;}'
                            ],
                            [
                                'user_id' => $user_id,
                                'meta_key' => 'mb_user_level',
                                'meta_value' => '0'
                            ]
                        ]);

                    $rs = [
                        'status' => 'success',
                        'data' => $this->getUserInfo($user_id)
                    ];
                } else {
                    $rs = [
                        'status' => 'error',
                        'message' => 'Đã xảy ra lỗi, vui lòng thử lại.'
                    ];
                }

                return $rs;
            });
        } else {
            DB::table($this->table_user)
                ->where('user_login', $data['email'])
                ->update([
                    'display_name' => $data['name']
                ]);
            Cache::forget("App\\User::getUserInfo" . $is_exist->ID);

            $result = [
                'status' => 'success',
                'data' => $this->getUserInfo($is_exist->ID)
            ];
        }

        return $result;
    }


    /**
     * Đổi password
     *
     * @param $request
     *
     * @return array
     */
    public function changePassword($request)
    {
        $data = $request->input();
        $cls_password = new PasswordHash(8, true);

        //update mật khẩu
        $hash_pw = $cls_password->HashPassword($data['new_pw']);
        DB::update("UPDATE {$this->table_user} SET user_pass = ? WHERE ID = ?", [$hash_pw, $data['user_id']]);

        $result = [
            'status' => 'success',
            'data' => 'Thay đổi mật khẩu thành công.'
        ];

        return $result;
    }


    /**
     * Update thông tin account
     *
     * @param $request
     *
     * @return array
     */
    public function updateAccount($request)
    {
        $data = $request->input();

        //trim
        $data['name'] = trim(valueOrNull($data['name']));
        $data['phone'] = trim(valueOrNull($data['phone']));

        if (!empty($data['user_id'])) {
            $result = DB::transaction(function () use ($data) {
                //update user
                DB::table($this->table_user)
                    ->where('ID', $data['user_id'])
                    ->update(['display_name' => $data['name']]);

                //delete getUserInfo cache
                Cache::forget("App\\User::getUserInfo" . $data['user_id']);

                return [
                    'status' => 'success',
                    'data' => $this->getUserInfo($data['user_id'])
                ];
            });
        } else {
            $result = [
                'status' => 'error',
                'message' => ['Đã xảy ra lỗi, vui lòng thử lại.']
            ];
        }

        return $result;
    }


    public function uploadAvatar($request)
    {
        $data = $request->input();

        $data['file_name'] = 'avatar';
        $result = $this->uploadImage($data);

        if ($result['status'] == 'success') {
            DB::table($this->table_user_info)
                ->where('user_id', $data['user_id'])
                ->update(['avatar' => $result['data']]);
        }

        return $result;
    }

    public function getToken($request){
        return csrf_token();
    }
}