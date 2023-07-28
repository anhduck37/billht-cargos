<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\User;
use Illuminate\Http\Request;
use Flash;
use Illuminate\Support\Facades\Hash;
use Response;

class UserController extends AppBaseController
{
    /** @var  userRepository */
    private $userRepository;
    public $userService;

    public function __construct(UserRepository $userRepo, UserService $userService)
    {
        $this->userRepository = $userRepo;
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $formFilter = $request-> all();
        $pageSize = intval(env('PAGE_SIZE'));
        if( !empty($formFilter) && empty($formFilter['email'])){
            unset($formFilter['email']);
        }
        
        if( !empty($formFilter) && empty($formFilter['status'])){
            unset($formFilter['status']);
        }

        if( !empty($formFilter) && empty($formFilter['level'])){
            unset($formFilter['level']);
        }

        $users = $this->userRepository->allQuery($formFilter);
        $users = $users->paginate($pageSize);

        return view('users.index')
            ->with('users', $users);
    }

    public function create()
    {
        return view('users.create', ['user' => new \App\User()]);
    }

    public function store(CreateUserRequest $request)
    {
        $dataForm = $request->all();
        if(array_key_exists('password', $dataForm) && $dataForm['password'] != '') {
            $dataForm['password'] = Hash::make($dataForm['password']);
        }

        $this->userRepository->create($dataForm);
        Flash::success('Tao tài khoản thành công.');

        return redirect(route('users.index'));

    }

    /**
     * Display the specified user.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $user = $this->userRepository->find($id);
        if (empty($user)) {
            Flash::error('Tài khoản không tồn tại.');

            return redirect(route('users.index'));
        }
        return view('users.show', ['user' => $user]);
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $user = $this->userRepository->find($id);
        if (empty($user)) {
            Flash::error('user not found');
            return redirect(route('users.index'));
        }

        return view('users.edit', ['user' => $user, 'update' => true]);
    }

    /**
     * Update the specified user in storage.
     *
     * @param int $id
     * @param UpdateUserRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateUserRequest $request)
    {
        $user = $this->userRepository->find($id);
        if (empty($user)) {
            Flash::error('Tài khoản này không tồn tại');
            return redirect(route('users.index'));
        }
        $formData = $request->all();
        if (array_key_exists('password', $formData) && $formData['password'] != '') {
            $password = $formData['password'];
            $formData['password'] = Hash::make($formData['password']);
        } else {
            unset($formData['password']);
        }
        if (array_key_exists('email', $formData) && auth()->user()->level != User::LEVEL_ADMIN) {
            unset($formData['email']);
        } else {
            $email = $request->email;
            if($email && $user->email  != $email) {
                $findUser = User::where('email',  $email)->first();
                if($findUser) {
                    Flash::error('Email ' . $email . ' này đã tồn tại');
                    return back();
                }
            }
        }
        $user = $this->userRepository->update($formData, $id);

        Flash::success('Cập nhật tài khoản thành công.');

        return redirect(route('users.index'));
    }

    /**
     * Remove the specified user from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $user = $this->userRepository->find($id);

        if (empty($user)) {
            Flash::error('user not found');

            return redirect(route('users.index'));
        }

        $this->userRepository->delete($id);

        Flash::success('user deleted successfully.');

        return redirect(route('users.index'));
    }

    public function showFormPassword($id)
    {
        return view('users.change_password')->with('id', $id);
    }

    public function updatePassword(UpdateUserPasswordRequest $updateUserPasswordRequest,$id){
        $user = $this->userRepository->find($id);
        if (empty($user)) {
            Flash::error('user not found');

            return redirect(route('users.index'));
        }
        $password = Hash::make($updateUserPasswordRequest->password);
        $this->userRepository->update(['password' => $password], $id );
        return redirect(route('users.index'));
    }
}
