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

            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>{{ __('messages.discord_name') }}</label>
                        <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.email') }}</label>
                        <input type="email" class="form-control" value="{{ $user->email }}" disabled>
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
                @if($user->discord_avatar)
                    <img src="{{ $user->discord_avatar }}" class="img-circle elevation-2 mb-3" alt="User Image" style="width: 100px; height: 100px;">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" class="img-circle elevation-2 mb-3" alt="User Image" style="width: 100px; height: 100px;">
                @endif
                <h4>{{ $user->name }}</h4>
                <p class="text-muted">{{ __('messages.id_label') }} {{ $user->discord_id }}</p>
            </div>
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

                <script>
                    function selectTheme(type, value) {
                        document.getElementById('input_' + type).value = value;
                        var items = document.querySelectorAll('.theme-item-' + type);
                        items.forEach(function(item) {
                            item.style.border = '1px solid #ddd';
                        });

                        var id = 'item_' + type + '_' + (value ? value.replace(/ /g, '_') : 'default');
                        var selected = document.getElementById(id);
                        if (selected) {
                            selected.style.border = '2px solid #007bff';
                        }
                    }
                </script>
                <div class="card-footer">
                    <button type="submit" class="btn btn-secondary">{{ __('messages.update_theme') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
