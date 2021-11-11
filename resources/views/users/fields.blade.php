
     <div class="form-group{{ $errors->has('name') ? ' has-danger' : '' }}">
         <div class="input-group input-group-alternative mb-3">
             <div class="input-group-prepend">
                 <span class="input-group-text"><i class="ni ni-hat-3"></i></span>
             </div>
             <input class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" placeholder="{{ __('Name') }}" type="text" name="name" value="{{ old('name') ? old('name'): $user->name }}" autofocus>
         </div>
         @if ($errors->has('name'))
             <span class="invalid-feedback" style="display: block;" role="alert">
                 <strong>{{ $errors->first('name') }}</strong>
             </span>
         @endif
     </div>
     <div class="form-group{{ $errors->has('email') ? ' has-danger' : '' }}">
         <div class="input-group input-group-alternative mb-3">
             <div class="input-group-prepend">
                 <span class="input-group-text"><i class="ni ni-email-83"></i></span>
             </div>
             <input class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" placeholder="{{ __('Email') }}" type="email" name="email" value="{{ old('email') ? old('email') : $user->email }}">
         </div>
         @if ($errors->has('email'))
             <span class="invalid-feedback" style="display: block;" role="alert">
                 <strong>{{ $errors->first('email') }}</strong>
             </span>
         @endif
     </div>
     <div class="form-group{{ $errors->has('status') ? ' has-danger' : '' }}">
         <div class="input-group input-group-alternative mb-3">
             <div class="input-group-prepend">
                 <span class="input-group-text"><i class="ni ni-app"></i></span>
             </div>
             <select class="form-control{{ $errors->has('status') ? ' is-invalid' : '' }}" name="status" >
                 @foreach(App\User::STATUS_MAP as $key => $status)
                     <option value="{{$key}}" {{ $user->status == $key ? 'selected' : '' }}>{{$status}}</option>
                 @endforeach
             </select>
         </div>
     </div>
     <div class="form-group{{ $errors->has('level') ? ' has-danger' : '' }}">
         <div class="input-group input-group-alternative mb-3">
             <div class="input-group-prepend">
                 <span class="input-group-text"><i class="ni ni-single-02"></i></span>
             </div>
             <select class="form-control{{ $errors->has('level') ? ' is-invalid' : '' }}" name="level" value="{{ old('level') }}">
                 @foreach(App\User::LEVEL_MAP as $key => $level)
                 <option value="{{$key}}" {{ $user->level == $key ? 'selected' : '' }}>{{$level}}</option>
                 @endforeach
             </select>
         </div>
     </div>
     <div class="form-group{{ $errors->has('password') ? ' has-danger' : '' }}">
         <div class="input-group input-group-alternative">
             <div class="input-group-prepend">
                 <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
             </div>
             <input class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="{{ __('Password') }}" type="password" name="password">
         </div>
         @if ($errors->has('password'))
             <span class="invalid-feedback" style="display: block;" role="alert">
                 <strong>{{ $errors->first('password') }}</strong>
             </span>
         @endif
     </div>


