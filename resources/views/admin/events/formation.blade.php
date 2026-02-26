@extends('layouts.admin')

@section('title', __('messages.sort_formation') . ' - ' . $event->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/guild_war/css/style.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/guild_war/css/formation.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/leaflet/leaflet.css') }}">
@endpush

@section('content')
<div class="guild-war-app">
    <div class="app-header d-flex justify-content-between align-items-center mb-3 p-3 bg-white shadow-sm rounded">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color: #4a5568;">{{ __('messages.guild_war_strategy_title') }}</h1>
            <small class="text-muted">{{ __('messages.guild_war_strategy_subtitle') }}</small>
        </div>
        <div class="header-controls d-flex align-items-center">
            <div class="mr-3 d-flex align-items-center">
                <input type="text" id="formationNameInput" class="form-control form-control-sm mr-2" placeholder="{{ __('messages.formation_name_placeholder') }}" title="{{ __('messages.formation_name_tooltip') }}">
                <select id="loadFormationSelect" class="form-control form-control-sm" style="width: 200px;" title="{{ __('messages.load_formation_tooltip') }}">
                    <option value="">{{ __('messages.load_formation_placeholder') }}</option>
                </select>
            </div>
            <button id="saveFormationBtn" class="btn btn-sm btn-success mr-2">
                <i class="fas fa-save"></i> {{ __('messages.save') }}
            </button>
            <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
                <i class="fas fa-arrow-left"></i> {{ __('messages.back_to_events') }}
            </a>
            <div class="menu-dropdown position-relative">
                <button id="menuBtn" class="btn btn-outline-primary btn-sm rounded-circle" style="width: 32px; height: 32px; padding: 0;"><i class="fas fa-bars"></i></button>
                <div id="menuContent" class="menu-content position-absolute bg-white shadow rounded p-2 mt-1" style="display: none; right: 0; min-width: 200px; z-index: 1000;">
                    <button id="themeToggleBtn" class="btn btn-block btn-sm btn-light text-left mb-1">
                        <i class="fas fa-adjust"></i> {{ __('messages.toggle_theme') }}
                    </button>
                    <button id="hotKeyBtn" class="btn btn-block btn-sm btn-light text-left">
                        <i class="fas fa-keyboard"></i> {{ __('messages.keyboard_shortcuts') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">
        <!-- Member Panel -->
        <div class="member-panel">
            <h2>
                {{ __('messages.guild_members') }} <span id="playerCount" class="badge badge-light ml-2" style="font-size: 0.8rem;">0/30</span>
                <button id="managePlayersBtn" class="btn btn-sm btn-outline-primary" title="Xếp Team">
                    {{ __('messages.assign_team') }}
                </button>
            </h2>

            <div class="enemy-section mb-3">
                <button id="addEnemiesBtn" class="add-enemies-btn">
                    <i class="fas fa-swords"></i> {{ __('messages.add_enemies') }} (<span id="enemyCount">0</span>/30)
                </button>
            </div>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="{{ __('messages.search_players') }}">
            </div>

            <div class="view-toggle">
                <button class="view-toggle-btn active" data-view="grouped">{{ __('messages.view_grouped_by_team') }}</button>
                <button class="view-toggle-btn" data-view="list">{{ __('messages.view_all_players') }}</button>
            </div>

            <div class="role-filters">
                <button class="role-filter-btn active" data-role="all">{{ __('messages.all_roles') }}</button>
                <button class="role-filter-btn" data-role="Tank">{{ __('messages.role_tank') }}</button>
                <button class="role-filter-btn" data-role="DPS">{{ __('messages.role_dps') }}</button>
                <button class="role-filter-btn" data-role="Healer">{{ __('messages.role_healer') }}</button>
            </div>

            <div id="memberList" class="member-list">
                <!-- Populated by JS -->
            </div>

            <!-- Footer Stats/Export -->
            <div class="panel-footer mt-auto pt-3 border-top">
                <div class="d-flex justify-content-between mb-2">
                    <small>{{ __('messages.placed_label') }} <span id="placedCount">0</span></small>
                </div>
                <div class="d-flex gap-2">
                     <button id="exportBtn" class="btn btn-primary btn-sm flex-fill mr-1"><i class="fas fa-file-export"></i> {{ __('messages.export') }}</button>
                     <button id="importBtn" class="btn btn-info btn-sm flex-fill ml-1"><i class="fas fa-file-import"></i> {{ __('messages.import') }}</button>
                     <input type="file" id="importFileInput" style="display:none">
                </div>
                <div class="mt-2">
                    <button id="saveFormationBtnBottom" class="btn btn-success btn-sm btn-block"><i class="fas fa-save"></i> {{ __('messages.save_formation') }}</button>
                </div>
            </div>
        </div>

        <!-- Map Area -->
        <div class="map-container">
            <div id="mapArea" class="map-area" style="flex: 1; width: 100%; height: 100%; position: relative; overflow: hidden; border-radius: 8px; border: 1px solid #eee;">
                <!-- Leaflet Map will be initialized here -->
            </div>
            <!-- Hidden canvas for app.js compatibility -->
            <canvas id="drawingCanvas" style="display: none;"></canvas>

            <!-- Bottom Toolbar -->
            <div class="map-toolbar mt-3">
                <button id="addObjectiveBtn" class="toolbar-btn" title="{{ __('messages.objective') }}" draggable="true" data-type="objective"><i class="fas fa-bullseye" style="color: #e74c3c;"></i></button>
                <div class="toolbar-divider"></div>

                <button id="addBossBtn" class="toolbar-btn" title="{{ __('messages.boss') }}" draggable="true" data-type="boss"><img src="{{ asset('assets/guild_war/images/boss.png') }}" alt="{{ __('messages.boss') }}" class="btn-icon-img"></button>
                <div class="toolbar-divider"></div>

                <button id="addBlueTowerBtn" class="toolbar-btn" title="{{ __('messages.blue_tower') }}" draggable="true" data-type="blue-tower"><img src="{{ asset('assets/guild_war/images/tower_blue.png') }}" alt="{{ __('messages.blue_tower') }}" class="btn-icon-img"></button>
                <button id="addRedTowerBtn" class="toolbar-btn" title="{{ __('messages.red_tower') }}" draggable="true" data-type="red-tower"><img src="{{ asset('assets/guild_war/images/tower_red.png') }}" alt="{{ __('messages.red_tower') }}" class="btn-icon-img"></button>

                <button id="addBlueTreeBtn" class="toolbar-btn" title="{{ __('messages.blue_tree') }}" draggable="true" data-type="blue-tree"><img src="{{ asset('assets/guild_war/images/tree_blue.png') }}" alt="{{ __('messages.blue_tree') }}" class="btn-icon-img"></button>
                <button id="addRedTreeBtn" class="toolbar-btn" title="{{ __('messages.red_tree') }}" draggable="true" data-type="red-tree"><img src="{{ asset('assets/guild_war/images/tree_red.png') }}" alt="{{ __('messages.red_tree') }}" class="btn-icon-img"></button>

                <button id="addBlueGooseBtn" class="toolbar-btn" title="{{ __('messages.blue_goose') }}" draggable="true" data-type="blue-goose"><img src="{{ asset('assets/guild_war/images/goose_blue.png') }}" alt="{{ __('messages.blue_goose') }}" class="btn-icon-img"></button>
                <button id="addRedGooseBtn" class="toolbar-btn" title="{{ __('messages.red_goose') }}" draggable="true" data-type="red-goose"><img src="{{ asset('assets/guild_war/images/goose_red.png') }}" alt="{{ __('messages.red_goose') }}" class="btn-icon-img"></button>

                <div class="toolbar-divider"></div>

                <!-- Drawing Tools -->
                <button id="drawBtn" class="toolbar-btn" title="{{ __('messages.draw') }}"><i class="fas fa-pencil-alt"></i></button>
                <input type="color" id="drawColorPicker" value="#ff0000" style="width: 30px; height: 30px; border: none; background: none; padding: 0;">
                <button id="undoDrawBtn" class="toolbar-btn" title="{{ __('messages.undo') }}"><i class="fas fa-undo"></i></button>
                <button id="redoDrawBtn" class="toolbar-btn" title="{{ __('messages.redo') }}"><i class="fas fa-redo"></i></button>
                <button id="clearDrawBtn" class="toolbar-btn" title="{{ __('messages.clear_drawings') }}"><i class="fas fa-eraser"></i></button>

                <div class="ml-2 d-flex align-items-center">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="autoDeleteToggle">
                        <label class="custom-control-label small" for="autoDeleteToggle">{{ __('messages.auto_delete') }}</label>
                    </div>
                </div>

                <button id="clearMapBtn" class="toolbar-btn ml-auto text-danger" title="{{ __('messages.clear_map') }}"><i class="fas fa-times"></i></button>
            </div>
        </div>
    </div>
</div>

    <!-- Modals -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3 id="confirmModalTitle">{{ __('messages.confirm_title') }}</h3>
            <p id="confirmModalMessage">{{ __('messages.are_you_sure') }}</p>
            <div class="modal-actions text-right">
                <button id="confirmCancelBtn" class="btn btn-secondary">{{ __('messages.cancel') }}</button>
                <button id="confirmOkBtn" class="btn btn-primary">{{ __('messages.ok') }}</button>
            </div>
        </div>
    </div>

    <div id="promptModal" class="modal">
        <div class="modal-content">
            <h3 id="promptModalTitle">{{ __('messages.prompt_title') }}</h3>
            <p id="promptModalMessage">{{ __('messages.prompt_message_default') }}</p>
            <input type="text" id="promptModalInput" class="form-control mb-3">
            <div class="modal-actions text-right">
                <button id="promptCancelBtn" class="btn btn-secondary">{{ __('messages.cancel') }}</button>
                <button id="promptOkBtn" class="btn btn-primary">{{ __('messages.ok') }}</button>
            </div>
        </div>
    </div>

    <div id="playerManagementModal" class="modal">
        <div class="modal-content" style="width: 600px;">
            <div class="modal-header">
                <h3>{{ __('messages.player_management_title') }}</h3>
                <button id="closeModalBtn" class="close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="playerManagementList" style="max-height: 400px; overflow-y: auto;">
                    <!-- List of players to edit/delete -->
                </div>
            </div>
        </div>
    </div>

    <div id="playerEditModal" class="modal">
        <div class="modal-content">
            <h3>{{ __('messages.edit_player_title') }}</h3>
            <form id="playerEditForm">
                <div class="form-group">
                    <label>{{ __('messages.name') }}</label>
                    <input type="text" id="editPlayerName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.role') }}</label>
                    <select id="editPlayerRole" class="form-control">
                        @foreach($skills as $skill)
                        <option value="{{ $skill->name }}">{{ $skill->name }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Add Team if needed -->
                <div class="form-group text-right">
                    <button type="button" id="cancelEditBtn" class="btn btn-secondary">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <!-- Leaflet JS -->
    <script src="{{ asset('assets/leaflet/leaflet.js') }}"></script>

    <script>
        // Inject Laravel data
        window.initialMembers = @json($participants);
        window.saveFormationUrl = "{{ route('admin.events.formation.save', $event) }}";
        window.initialFormationData = @json($event->formation_data);
        window.pastEvents = @json($pastEvents);
        window.csrfToken = "{{ csrf_token() }}";
        // Map Image URL
        window.mapImageUrl = "{{ asset('assets/guild_war/images/map.png') }}";
    </script>
    <script src="{{ asset('assets/guild_war/js/app.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('assets/guild_war/js/leaflet_map.js') }}?v={{ time() }}"></script>
@endpush
