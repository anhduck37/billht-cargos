
     <div class="form-group{{ $errors->has('name') ? ' has-danger' : '' }}">
         <div class="input-group input-group-alternative mb-3">
             <div class="input-group-prepend">
                 <span class="input-group-text"><i class="fa fa-building"></i></span>
             </div>
             <input class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" placeholder="{{ __('Name') }}" type="text" name="name" value="{{ old('name') ? old('name'): $partner->name }}" autofocus>
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
                 <span class="input-group-text"><i class="fa fa-cubes"></i></span>
             </div>
             <input class="form-control{{ $errors->has('prefix_code') ? ' is-invalid' : '' }}" placeholder="{{ __('Prefix code') }}" type="text" name="prefix_code" value="{{ old('prefix_code') ? old('prefix_code') : $partner->prefix_code }}">
         </div>
         @if ($errors->has('prefix_code'))
             <span class="invalid-feedback" style="display: block;" role="alert">
                 <strong>{{ $errors->first('prefix_code') }}</strong>
             </span>
         @endif
     </div>


