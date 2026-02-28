@extends('layouts.admin')

@section('title', __('messages.edit_profile_title'))

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.user_information') }}</h3>
            </div>

            @if (session('success'))
                <div class="alert alert-success m-3">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>{{ $user->discord_id ? __('messages.discord_name') : __('messages.name') }}</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}" {{ $user->discord_id ? 'disabled' : '' }}>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.account') }}</label>
                        <input type="text" class="form-control" name="account" value="{{ old('account', $user->account) }}" {{ $user->discord_id ? 'disabled' : '' }}>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.email') }}</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" {{ $user->discord_id ? 'disabled' : '' }}>
                    </div>
                    <div class="form-group">
                        <label for="avatar">{{ __('messages.avatar') }}</label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="avatar" name="avatar" onchange="previewImage(this)">
                                <label class="custom-file-label" for="avatar">{{ __('messages.upload_avatar') }}</label>
                            </div>
                        </div>
                        <div class="mt-2" id="avatar-preview-container" style="display: none;">
                            <img id="avatar-preview" src="#" alt="Avatar Preview" class="img-circle elevation-2" style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="country">{{ __('messages.country') }}</label>
                        <input type="text" class="form-control" id="country" name="country" value="{{ old('country', $user->country) }}" placeholder="{{ __('messages.enter_country') }}">
                    </div>
                    <div class="row">
                        <div class="col-6">
                             <div class="form-group">
                                <label for="online_from">{{ __('messages.online_from_24h') }}</label>
                                <input type="time" class="form-control" id="online_from" name="online_from" value="{{ old('online_from', $user->online_from) }}" step="60">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="online_to">{{ __('messages.online_to_24h') }}</label>
                                <input type="time" class="form-control" id="online_to" name="online_to" value="{{ old('online_to', $user->online_to) }}" step="60">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ingame_name">{{ __('messages.ingame_name') }}</label>
                        <input type="text" class="form-control" id="ingame_name" name="ingame_name" value="{{ old('ingame_name', $user->ingame_name) }}" placeholder="{{ __('messages.enter_ingame_name') }}">
                    </div>
                    <div class="form-group">
                        <label for="ingame_id">{{ __('messages.ingame_id') }}</label>
                        <input type="text" class="form-control" id="ingame_id" name="ingame_id" value="{{ old('ingame_id', $user->ingame_id) }}" placeholder="{{ __('messages.enter_ingame_id') }}">
                    </div>
                    <div class="row" v-pre>
                        <div class="col-6">
                            <div class="form-group">
                                <label>{{ __('messages.main_skill_label') }}</label>
                                <div class="form-control" disabled style="height: auto; display: flex; align-items: center;">
                                    @if($user->mainSkill)
                                        <img src="{{ asset('icon/skill/' . $user->mainSkill->icon) }}" width="30" height="30" class="mr-2">
                                        <span>{{ $user->mainSkill->name }}</span>
                                    @else
                                        <span class="text-muted">{{ __('messages.none_text') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                             <div class="form-group">
                                <label>{{ __('messages.sub_skill_label') }}</label>
                                <div class="form-control" disabled style="height: auto; display: flex; align-items: center;">
                                    @if($user->subSkill)
                                        <img src="{{ asset('icon/skill/' . $user->subSkill->icon) }}" width="30" height="30" class="mr-2">
                                        <span>{{ $user->subSkill->name }}</span>
                                    @else
                                        <span class="text-muted">{{ __('messages.none_text') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">{{ __('messages.update_profile') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-info">
             <div class="card-header">
                <h3 class="card-title">{{ __('messages.discord_info') }}</h3>
            </div>
            <div class="card-body text-center">
                @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" class="img-circle elevation-2 mb-3" alt="User Image" style="width: 100px; height: 100px; object-fit: cover;">
                @elseif($user->discord_avatar)
                    <img src="{{ $user->discord_avatar }}" class="img-circle elevation-2 mb-3" alt="User Image" style="width: 100px; height: 100px;">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" class="img-circle elevation-2 mb-3" alt="User Image" style="width: 100px; height: 100px;">
                @endif
                <h4>{{ $user->name }}</h4>
                <p class="text-muted">{{ __('messages.id_label') }} {{ $user->discord_id }}</p>

                <hr>
                <a href="{{ route('auth.discord') }}" class="btn btn-block btn-primary" style="background-color: #7289da; border-color: #7289da;">
                    <i class="fab fa-discord mr-2"></i>
                    {{ $user->discord_id ? __('messages.sync_discord') : __('messages.link_discord') }}
                </a>
            </div>
        </div>

        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.change_password') }}</h3>
            </div>
            <form action="{{ route('profile.password.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @if($user->password)
                    <div class="form-group">
                        <label for="current_password">{{ __('messages.current_password') }}</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password">
                        @error('current_password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    @endif
                    <div class="form-group">
                        <label for="password">{{ __('messages.new_password') }}</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">{{ __('messages.confirm_password') }}</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">{{ __('messages.update_password') }}</button>
                </div>
            </form>
        </div>

        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.theme_settings') }}</h3>
            </div>
            <form action="{{ route('profile.theme.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @php
                        $navbarVariants = [
                            'navbar-primary navbar-dark', 'navbar-secondary navbar-dark', 'navbar-info navbar-dark', 'navbar-success navbar-dark', 'navbar-danger navbar-dark', 'navbar-indigo navbar-dark', 'navbar-purple navbar-dark', 'navbar-pink navbar-dark', 'navbar-navy navbar-dark', 'navbar-lightblue navbar-dark', 'navbar-teal navbar-dark', 'navbar-cyan navbar-dark', 'navbar-dark navbar-dark', 'navbar-gray-dark navbar-dark', 'navbar-gray navbar-dark', 'navbar-light navbar-light', 'navbar-warning navbar-light', 'navbar-white navbar-light', 'navbar-orange navbar-light'
                        ];
                        $sidebarVariants = [
                            'sidebar-dark-primary', 'sidebar-dark-warning', 'sidebar-dark-info', 'sidebar-dark-danger', 'sidebar-dark-success', 'sidebar-dark-indigo', 'sidebar-dark-lightblue', 'sidebar-dark-navy', 'sidebar-dark-purple', 'sidebar-dark-fuchsia', 'sidebar-dark-pink', 'sidebar-dark-maroon', 'sidebar-dark-orange', 'sidebar-dark-lime', 'sidebar-dark-teal', 'sidebar-dark-olive',
                            'sidebar-light-primary', 'sidebar-light-warning', 'sidebar-light-info', 'sidebar-light-danger', 'sidebar-light-success', 'sidebar-light-indigo', 'sidebar-light-lightblue', 'sidebar-light-navy', 'sidebar-light-purple', 'sidebar-light-fuchsia', 'sidebar-light-pink', 'sidebar-light-maroon', 'sidebar-light-orange', 'sidebar-light-lime', 'sidebar-light-teal', 'sidebar-light-olive'
                        ];
                        $accentVariants = [
                            'accent-primary', 'accent-warning', 'accent-info', 'accent-danger', 'accent-success', 'accent-indigo', 'accent-lightblue', 'accent-navy', 'accent-purple', 'accent-fuchsia', 'accent-pink', 'accent-maroon', 'accent-orange', 'accent-lime', 'accent-teal', 'accent-olive'
                        ];
                        $brandVariants = [
                            'navbar-primary', 'navbar-secondary', 'navbar-info', 'navbar-success', 'navbar-danger', 'navbar-indigo', 'navbar-purple', 'navbar-pink', 'navbar-navy', 'navbar-lightblue', 'navbar-teal', 'navbar-cyan', 'navbar-dark', 'navbar-gray-dark', 'navbar-gray', 'navbar-light', 'navbar-warning', 'navbar-white', 'navbar-orange'
                        ];
                        $backgroundVariants = [
                            'bg-primary', 'bg-secondary', 'bg-info', 'bg-success', 'bg-danger', 'bg-indigo', 'bg-purple', 'bg-pink', 'bg-navy', 'bg-lightblue', 'bg-teal', 'bg-cyan', 'bg-white', 'bg-gray', 'bg-gray-dark'
                        ];

                        function getBgClass($variant, $type) {
                            if ($type == 'navbar' || $type == 'brand') {
                                 $color = explode(' ', $variant)[0];
                                 $color = str_replace('navbar-', '', $color);
                                 if ($color == 'white') return 'bg-white';
                                 if ($color == 'light') return 'bg-light';
                                 return 'bg-' . $color;
                            }
                            if ($type == 'sidebar') {
                                 $parts = explode('-', $variant);
                                 $color = end($parts);
                                 return 'bg-' . $color;
                            }
                            if ($type == 'accent') {
                                 $color = str_replace('accent-', '', $variant);
                                 return 'bg-' . $color;
                            }
                            if ($type == 'background') {
                                return $variant;
                            }
                            return 'bg-gray';
                        }
                    @endphp

                    <!-- Dark Mode -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="dark_mode" name="dark_mode" {{ ($user->themeSetting->dark_mode ?? false) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="dark_mode">{{ __('messages.dark_mode') }}</label>
                        </div>
                    </div>

                    <!-- Navbar Variant -->
                    <div class="form-group">
                        <label>{{ __('messages.navbar_variant') }}</label>
                        <input type="hidden" name="navbar_variant" id="input_navbar" value="{{ $user->themeSetting->navbar_variant ?? '' }}">
                        <div class="d-flex flex-wrap" style="max-height: 200px; overflow-y: auto;">
                            <div class="mr-2 mb-2 text-center theme-item theme-item-navbar"
                                 id="item_navbar_default"
                                 onclick="selectTheme('navbar', '')"
                                 style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->navbar_variant ?? '') == '' ? 'border: 2px solid #007bff;' : '' }}">
                                <div class="bg-light elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                <div style="font-size: 10px;">{{ __('messages.default') }}</div>
                            </div>
                            @foreach($navbarVariants as $variant)
                                @php $bg = getBgClass($variant, 'navbar'); @endphp
                                <div class="mr-2 mb-2 text-center theme-item theme-item-navbar"
                                     id="item_navbar_{{ str_replace(' ', '_', $variant) }}"
                                     onclick="selectTheme('navbar', '{{ $variant }}')"
                                     style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->navbar_variant ?? '') == $variant ? 'border: 2px solid #007bff;' : '' }}">
                                    <div class="{{ $bg }} elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                    <div style="font-size: 10px;">{{ str_replace('navbar-', '', explode(' ', $variant)[0]) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Sidebar Variant -->
                    <div class="form-group">
                        <label>{{ __('messages.sidebar_variant') }}</label>
                        <input type="hidden" name="sidebar_variant" id="input_sidebar" value="{{ $user->themeSetting->sidebar_variant ?? '' }}">
                        <div class="d-flex flex-wrap" style="max-height: 200px; overflow-y: auto;">
                            <div class="mr-2 mb-2 text-center theme-item theme-item-sidebar"
                                 id="item_sidebar_default"
                                 onclick="selectTheme('sidebar', '')"
                                 style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->sidebar_variant ?? '') == '' ? 'border: 2px solid #007bff;' : '' }}">
                                <div class="bg-primary elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                <div style="font-size: 10px;">{{ __('messages.default') }}</div>
                            </div>
                            @foreach($sidebarVariants as $variant)
                                @php $bg = getBgClass($variant, 'sidebar'); $isLight = strpos($variant, 'light') !== false; @endphp
                                <div class="mr-2 mb-2 text-center theme-item theme-item-sidebar"
                                     id="item_sidebar_{{ $variant }}"
                                     onclick="selectTheme('sidebar', '{{ $variant }}')"
                                     style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->sidebar_variant ?? '') == $variant ? 'border: 2px solid #007bff;' : '' }}">
                                    <div class="{{ $bg }} elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px; border: {{ $isLight ? '1px solid #ccc' : 'none' }}"></div>
                                    <div style="font-size: 10px;">{{ str_replace('sidebar-', '', $variant) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Brand Logo Variant -->
                    <div class="form-group">
                        <label>{{ __('messages.brand_logo_variant') }}</label>
                        <input type="hidden" name="brand_logo_variant" id="input_brand" value="{{ $user->themeSetting->brand_logo_variant ?? '' }}">
                        <div class="d-flex flex-wrap" style="max-height: 200px; overflow-y: auto;">
                             <div class="mr-2 mb-2 text-center theme-item theme-item-brand"
                                 id="item_brand_default"
                                 onclick="selectTheme('brand', '')"
                                 style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->brand_logo_variant ?? '') == '' ? 'border: 2px solid #007bff;' : '' }}">
                                <div class="bg-light elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                <div style="font-size: 10px;">{{ __('messages.default') }}</div>
                            </div>
                            @foreach($brandVariants as $variant)
                                @php $bg = getBgClass($variant, 'brand'); @endphp
                                <div class="mr-2 mb-2 text-center theme-item theme-item-brand"
                                     id="item_brand_{{ $variant }}"
                                     onclick="selectTheme('brand', '{{ $variant }}')"
                                     style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->brand_logo_variant ?? '') == $variant ? 'border: 2px solid #007bff;' : '' }}">
                                    <div class="{{ $bg }} elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                    <div style="font-size: 10px;">{{ str_replace('navbar-', '', $variant) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Accent Color -->
                    <div class="form-group">
                        <label>{{ __('messages.accent_color') }}</label>
                         <input type="hidden" name="accent_color" id="input_accent" value="{{ $user->themeSetting->accent_color ?? '' }}">
                        <div class="d-flex flex-wrap" style="max-height: 200px; overflow-y: auto;">
                             <div class="mr-2 mb-2 text-center theme-item theme-item-accent"
                                 id="item_accent_default"
                                 onclick="selectTheme('accent', '')"
                                 style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->accent_color ?? '') == '' ? 'border: 2px solid #007bff;' : '' }}">
                                <div class="bg-light elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                <div style="font-size: 10px;">{{ __('messages.none_text') }}</div>
                            </div>
                            @foreach($accentVariants as $variant)
                                @php $bg = getBgClass($variant, 'accent'); @endphp
                                <div class="mr-2 mb-2 text-center theme-item theme-item-accent"
                                     id="item_accent_{{ $variant }}"
                                     onclick="selectTheme('accent', '{{ $variant }}')"
                                     style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->accent_color ?? '') == $variant ? 'border: 2px solid #007bff;' : '' }}">
                                    <div class="{{ $bg }} elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                    <div style="font-size: 10px;">{{ str_replace('accent-', '', $variant) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Background Color -->
                    <div class="form-group">
                        <label>{{ __('messages.background_color') }}</label>
                         <input type="hidden" name="background_color" id="input_background" value="{{ $user->themeSetting->background_color ?? '' }}">
                        <div class="d-flex flex-wrap" style="max-height: 200px; overflow-y: auto;">
                             <div class="mr-2 mb-2 text-center theme-item theme-item-background"
                                 id="item_background_default"
                                 onclick="selectTheme('background', '')"
                                 style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->background_color ?? '') == '' ? 'border: 2px solid #007bff;' : '' }}">
                                <div class="bg-light elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                <div style="font-size: 10px;">{{ __('messages.none_text') }}</div>
                            </div>
                            @foreach($backgroundVariants as $variant)
                                @php $bg = getBgClass($variant, 'background'); @endphp
                                <div class="mr-2 mb-2 text-center theme-item theme-item-background"
                                     id="item_background_{{ $variant }}"
                                     onclick="selectTheme('background', '{{ $variant }}')"
                                     style="cursor: pointer; border: 1px solid #ddd; padding: 5px; border-radius: 5px; {{ ($user->themeSetting->background_color ?? '') == $variant ? 'border: 2px solid #007bff;' : '' }}">
                                    <div class="{{ $bg }} elevation-1 mx-auto mb-1" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                    <div style="font-size: 10px;">{{ str_replace('bg-', '', $variant) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

@push('scripts')
<script>
    function selectTheme(type, value) {
        // Update hidden input
        $('#input_' + type).val(value);

        // Update UI selection
        $('.theme-item-' + type).css('border', '1px solid #ddd');
        if (value == '') {
            $('#item_' + type + '_default').css('border', '2px solid #007bff');
        } else {
             // Handle variants with spaces (navbar)
             let safeValue = value.replace(/ /g, '_');
             $('#item_' + type + '_' + safeValue).css('border', '2px solid #007bff');
        }
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                $('#avatar-preview').attr('src', e.target.result);
                $('#avatar-preview-container').show();
            }

            reader.readAsDataURL(input.files[0]);

            // Update file label
            var fileName = input.files[0].name;
            $(input).next('.custom-file-label').html(fileName);
        }
    }
</script>
@endpush
                <div class="card-footer">
                    <button type="submit" class="btn btn-secondary">{{ __('messages.update_theme') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
