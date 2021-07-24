<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\UserAddress;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\SendOtp;

class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'registration']]);
    }

    function verifyOtp(Request $request)
    {
        $id = $request->id;
        if ($request) {
            $user = User::where('id', '=', $id)->first();
            if ($user->code == $request->code) {
                $user->active = 1;
                $user->code = null;
                $user->save();
                return response()->json(['user' => $user]);
            }

            $detail = [
                'title' => 'Mail From balck Eye technalogies',
                'body' => 'This is Testing Email. from Black Eye Technologies'
            ];

            // Mail::to("abhi884707@gmail.com")->send(new SuccRegMail($detail));
        }
    }

    function login(Request $request)
    {

        $phone = '';
        $user = User::where('email', $request->email)->first();
        $credentials = request(['email', 'password']);
        if (!$user) {
            $user = User::where('phone', $request->email)->first();
            $credentials = ['phone' => $request->email, 'password' => $request->password];
        }
        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if($user->role_id == 3){
            $user = User::where('id','=',$user->id)
            ->select("users.id","users.name","users.email","users.phone","users.role_id")->get();
        }
        return $this->respondWithToken($token, $user);
    }

    function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $user,
        ]);
    }

    function registration(Request $request)
    {
        $rules = [
            'name' => "between:2,200",
            'email' => "unique:users,email",
            'mobile' => 'unique:users,mobile',
            'password' => 'max:50|min:8',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 401);
        } else {

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->password = $request->password;
            $user->active = 0;

            if ($request->roll == 3) {
                $user->role_id = 3;
            }
            if ($request->roll == 2) {
                // $user->address = ['house' => $request->house, 'village' => $request->village, 'town' => $request->town, 'distric' => $request->distric, 'state' => $request->state, 'zip' => $request->zip];

                // $user->mark_your_loc = $request->mark_loc;
                $user->whatsapp = $request->whatsapp;
            }
            if ($request->roll == 2 && $request->type == 1) {
                $user->gst_no = $request->gst;
                $user->contect_person = $request->con_per;
                $user->type_of_seller = $request->type;
                $user->startup_reg_no = $request->reg_no;
                $user->role_id = 2;
                if ($request->hasfile('file')) {
                    $user->image = $request->file('file')->store('FasslaImage/startupimg');
                }
            }
            if ($request->roll == 2 && $request->type == 2) {
                $user->type_of_seller = 2;
                $user->role_id = 2;
                if ($request->hasFile('file')) {
                    $user->image = $request->file('file')->store('FasslaImage/farmerimg');
                }
            }

            if($request->roll == 1) {
                $user->role_id = 1;
                $user->image = $request->file('image')->store('FasslaImage/adminimg');
            }

            // $user->code = SendOtp::sendOtp($request->phone);
            $user->code = 1234;
            $user->save();
            if($user->role_id == 2){
                $addr = UserAddress::create([
                    'userid' => $user->id,
                    'address' => ['house' => $request->house, 'village' => $request->village, 'town' => $request->town, 'distric' => $request->distric, 'state' => $request->state, 'zip' => $request->zip]
                ]);
                $user->save();
            }
            if ($user) {
                // Mail::to($user->email)->send(new WelcomeMail);
                // $data = ['name' => $user->name,'email'=>$user->eamil];
                // Mail::send('emails.mail', $data, function ($message) use($user) {
                // $message->from('abhi884707@gmail.com', 'Abhi Verma');
                // $message->sender('john@johndoe.com', 'John Doe');
                // $message->to($user->email, $user->name);
                // $message->cc('john@johndoe.com', 'John Doe');
                // $message->bcc('john@johndoe.com', 'John Doe');
                // $message->replyTo('john@johndoe.com', 'John Doe');
                // $message->subject('Hello From Testing Email');
                // $message->priority(3);
                // $message->attach('pathToFile');
                // });
                $token = JWTAuth::fromUser($user);
                if($user->role_id == 2){
                    $newuser = UserAddress::join("users","useraddress.userid","=","users.id")->where("users.id",$user->id)
                    ->select("users.id","users.name","users.email","users.phone","users.contect_person","useraddress.address","users.mark_your_loc","users.gst_no","users.whatsapp","users.image","users.startup_reg_no","users.type_of_seller","users.role_id")->first();
                    if($newuser){
                        return $this->respondWithToken($token, $newuser);
                    }
                }
                $user = User::where("id",$user->id) ->select("users.id","users.name","users.email","users.phone","users.role_id")->first();
                return $this->respondWithToken($token, $user);
            }
        }
    }


    function update(Request $request)
    {
        // $user = User::where('')
        $id = $request->id;
        $role_id = $request->role;
        $type_of_seller = $request->type;
        $user = User::where('id', $id)->first();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->password = $request->password;

        if ($request->roll == 3) {
            $user->role_id = 3;
        }
        if ($request->roll == 2) {
            $user->address = ['house' => $request->house, 'village' => $request->village, 'town' => $request->town, 'distric' => $request->distric, 'state' => $request->state, 'zip' => $request->zip];
            // $user->mark_your_loc = $request->mark_loc;
            $user->whatsapp = $request->whatsapp;
        }
        if ($request->roll == 2 && $request->type == 1) {
            $user->gst_no = $request->gst;
            $user->contect_person = $request->con_per;
            $user->type_of_seller = $request->type;
            $user->startup_reg_no = $request->reg_no;
            $user->role_id = 2;
            if ($request->hasfile('file')) {
                $img_arr = explode('/', $user->image);
                Storage::delete(['/FasslaImage/farmerimg/' . $img_arr[2]]);
                $user->image = $request->file('file')->store('FasslaImage/startupimg');
            }
        }
        if ($request->roll == 2 && $request->type == 2) {
            $user->type_of_seller = 2;
            $user->role_id = 2;
            if ($request->hasFile('file')) {
                $img_arr = explode('/', $user->image);
                Storage::delete(['/FasslaImage/farmerimg/' . $img_arr[2]]);
                $user->image = $request->file('file')->store('FasslaImage/farmerimg');
            }
        }

        if ($request->roll == 1) {
            $user->role_id = 1;
            $user->image = $request->file('image')->store('FasslaImage/adminimg');
        }

        // $user->code = SendOtp::sendOtp($request->phone);
        $user->code = 1234;
        $user->save();
        return response()->json(['user' => $user]);
    }


    function requestForUpdate(Request $request)
    {

        $user = User::where('id', $request->user_id)->first();
        $user->edit_request = $request->all();
        $user->save();
        return $user;
    }

    function requestList()
    {
        return User::whereNotNull('edit_request')->get();
    }

    function list(){
        return User::all();
    }

    function AddressList($id){
        $address = UserAddress::where('userid','=',$id)->get();
        return response()->json(['address' => $address]);
    }

}
