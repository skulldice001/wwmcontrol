@extends('layouts.admin')

@section('title', __('messages.sort_formation') . ' - ' . $event->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/guild_war/css/formation.css') }}">
    <style>
        /* OpenSeadragon container styles */
        #openseadragon-viewer {
            width: 100%;
            height: 100%;
            background-color: #2c3e50;
            border: 3px dashed #34495e;
            border-radius: 8px;
            position: relative; /* For absolute positioned overlays if needed */
        }
        
        /* Ensure markers are visible on top of OSD */
        .member-marker, .group-marker, .objective-marker, .boss-marker, .tower-marker, .tree-marker, .enemy-marker, .marker-tooltip {
            z-index: 100; /* OSD overlays usually handle z-index, but this helps if we use custom overlays */
            position: absolute; /* OSD expects absolute positioning for overlays */
            transform: translate(-50%, -50%); /* Center the marker on the coordinate */
        }
        
        /* Hide the old static map background if it was on a wrapper */
        .map-area {
            background: none !important;
            border: none !important;
        }
    </style>
@endpush

@section('content')
<div class="guild-war-app">
    <!-- Top Toolbar -->
    <div class="map-toolbar">
        <div class="toolbar-group">
            <button id="menuBtn" class="btn btn-outline-secondary d-md-none">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="m-0 d-none d-md-block">{{ $event->title }}</h5>
        </div>
        
        <div class="toolbar-divider"></div>
        
        <div class="toolbar-group">
            <button id="saveFormationBtn" class="btn btn-primary">
                <i class="fas fa-save"></i> <span class="d-none d-sm-inline">Save</span>
            </button>
            <button id="clearMapBtn" class="btn btn-danger">
                <i class="fas fa-trash"></i> <span class="d-none d-sm-inline">Clear Map</span>
            </button>
            <div class="dropdown d-inline-block">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="moreActionsDropdown" data-toggle="dropdown">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" id="exportBtn"><i class="fas fa-file-export"></i> Export JSON</a>
                    <a class="dropdown-item" href="#" id="importBtn"><i class="fas fa-file-import"></i> Import JSON</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#" id="hotKeyBtn"><i class="fas fa-keyboard"></i> Shortcuts</a>
                    <a class="dropdown-item" href="#" id="themeToggleBtn"><i class="fas fa-adjust"></i> Toggle Theme</a>
                </div>
            </div>
            <input type="file" id="importFileInput" style="display: none;" accept=".json">
        </div>

        <div class="toolbar-divider"></div>

        <div class="toolbar-group stats-group">
            <span id="playerCount" class="badge badge-info" title="Total Members">0/0</span>
            <span id="placedCount" class="badge badge-success" title="Placed on Map">0/0</span>
            <span id="enemyCount" class="badge badge-danger" title="Enemies">0</span>
        </div>
    </div>

    <div class="main-content">
        <!-- Left Panel: Member List -->
        <div class="member-panel">
            <div class="panel-header">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search members...">
                </div>
                <div class="filter-controls">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary active view-toggle-btn" data-view="grouped" title="Group by Team"><i class="fas fa-users"></i></button>
                        <button class="btn btn-outline-secondary view-toggle-btn" data-view="list" title="List View"><i class="fas fa-list"></i></button>
                    </div>
                </div>
            </div>
            
            <div class="role-filters">
                <button class="role-filter-btn active" data-role="all">All</button>
                <button class="role-filter-btn" data-role="Tank">Tank</button>
                <button class="role-filter-btn" data-role="DPS">DPS</button>
                <button class="role-filter-btn" data-role="Healer">Healer</button>
                <button class="role-filter-btn" data-role="Support">Support</button>
            </div>

            <div id="memberList" class="member-list">
                <!-- Members will be populated here -->
            </div>
            
            <div class="panel-footer">
                <button id="managePlayersBtn" class="btn btn-sm btn-outline-primary w-100">
                    <i class="fas fa-users-cog"></i> Manage Players
                </button>
            </div>
        </div>

        <!-- Right Panel: Map -->
        <div class="map-container">
            <!-- OpenSeadragon Viewer Container -->
            <!-- Note: We keep id="mapArea" wrapper for layout but the actual OSD will be inside or replace it -->
            <!-- Actually, app.js expects mapArea to be the drop target. 
                 We will make mapArea the OSD container. -->
            <div id="mapArea" class="map-area">
                <!-- OSD will inject content here -->
                <div id="openseadragon-viewer"></div>
                
                <!-- Placeholder for empty map -->
                <div class="map-placeholder" style="display: none; pointer-events: none; z-index: 0;">
                    <i class="fas fa-map-marked-alt fa-3x mb-3 text-muted"></i>
                    <p class="text-muted">Drag members or objects here</p>
                </div>
                
                <!-- Canvas for drawings (might need rework for OSD) -->
                <canvas id="drawingCanvas" class="drawing-canvas" style="display: none;"></canvas>
            </div>

            <!-- Map Tools (Bottom) -->
            <div class="map-tools">
                <!-- Placing Tools -->
                <div class="tools-section">
                    <div class="tool-label">Objects</div>
                    <div class="tool-buttons">
                        <button class="toolbar-btn" id="addObjectiveBtn" title="Objective (O)">
                            <i class="fas fa-flag"></i>
                        </button>
                        <button class="toolbar-btn" id="addBossBtn" title="Boss (B)">
                            <i class="fas fa-skull"></i>
                        </button>
                        <button class="toolbar-btn" id="addBlueTowerBtn" title="Blue Tower (1)">
                            <span class="icon-text" style="color: #3498db;">T</span>
                        </button>
                        <button class="toolbar-btn" id="addRedTowerBtn" title="Red Tower (2)">
                            <span class="icon-text" style="color: #e74c3c;">T</span>
                        </button>
                        <button class="toolbar-btn" id="addBlueTreeBtn" title="Blue Tree (3)">
                            <span class="icon-text" style="color: #3498db;">Tr</span>
                        </button>
                        <button class="toolbar-btn" id="addRedTreeBtn" title="Red Tree (4)">
                            <span class="icon-text" style="color: #e74c3c;">Tr</span>
                        </button>
                         <button class="toolbar-btn" id="addBlueGooseBtn" title="Blue Goose (5)">
                            <span class="icon-text" style="color: #3498db;">G</span>
                        </button>
                        <button class="toolbar-btn" id="addRedGooseBtn" title="Red Goose (6)">
                            <span class="icon-text" style="color: #e74c3c;">G</span>
                        </button>
                        <button class="toolbar-btn" id="addEnemiesBtn" title="Add 5 Enemies">
                            <i class="fas fa-user-ninja"></i>
                        </button>
                    </div>
                </div>

                <!-- Drawing Tools -->
                <div class="tools-section">
                    <div class="tool-label">Draw</div>
                    <div class="tool-buttons">
                        <button class="toolbar-btn" id="drawBtn" title="Draw Mode">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <input type="color" id="drawColorPicker" value="#ff0000" title="Color">
                        <button class="toolbar-btn" id="undoDrawBtn" title="Undo (Ctrl+Z)" disabled>
                            <i class="fas fa-undo"></i>
                        </button>
                        <button class="toolbar-btn" id="redoDrawBtn" title="Redo (Ctrl+Y)" disabled>
                            <i class="fas fa-redo"></i>
                        </button>
                        <button class="toolbar-btn" id="clearDrawBtn" title="Clear Drawings">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </div>
                    <div class="toggle-wrapper ml-2">
                        <label class="toggle-label" title="Auto-delete drawings after 10s">
                            <input type="checkbox" id="autoDeleteToggle">
                            <div class="toggle-slider"></div>
                            <span class="toggle-text">Auto-del</span>
                        </label>
                    </div>
                </div>
                
                <div class="tools-section ml-auto">
                    <button id="saveFormationBtnBottom" class="btn btn-primary btn-sm">
                        <i class="fas fa-save"></i> Save Formation
                    </button>
                </div>
            </div>
            
            <!-- Formation Loader -->
            <div class="formation-loader mt-2">
                 <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Load from Event:</span>
                    </div>
                    <select class="form-control" id="loadFormationSelect">
                        <option value="">-- Select Past Event --</option>
                    </select>
                </div>
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
            <button id="closeEditModalBtn" class="close" style="position:absolute; top:10px; right:10px;">&times;</button>
        </div>
    </div>

    <div id="hotkeyHelpModal" class="modal">
        <div class="modal-content">
            <h3>{{ __('messages.hotkey_title') }}</h3>
            <button id="closeHotkeyModalBtn" class="close">&times;</button>
            <ul>
                <li>{{ __('messages.shortcut_show_help') }}</li>
                <li>{{ __('messages.shortcut_esc_cancel_tool') }}</li>
                <li>{{ __('messages.shortcut_undo_drawing') }}</li>
                <li>{{ __('messages.shortcut_redo_drawing') }}</li>
                <li>{{ __('messages.shortcut_toggle_drawing') }}</li>
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Inject Laravel data
    window.initialMembers = @json($participants);
    window.saveFormationUrl = "{{ route('admin.events.formation.save', $event) }}";
    window.initialFormationData = @json($event->formation_data);
    window.pastEvents = @json($pastEvents);
    window.csrfToken = "{{ csrf_token() }}";
    window.roleTranslations = {
        'Tank': "{{ __('messages.role_tank') }}",
        'DPS': "{{ __('messages.role_dps') }}",
        'Healer': "{{ __('messages.role_healer') }}",
        'Unknown': "{{ __('messages.role_unknown') }}"
    };
    window.mapImageUrl = "{{ asset('assets/guild_war/images/map.png') }}";
    window.openseadragonImagesPath = "{{ asset('assets/openseadragon/openseadragon-bin-4.1.0/images/') }}/";
</script>
    <!-- OpenSeadragon (Local) -->
    <script src="{{ asset('assets/openseadragon/openseadragon-bin-4.1.0/openseadragon.min.js') }}"></script>
    <!-- App Logic -->
    <script src="{{ asset('assets/guild_war/js/app.js') }}?v={{ time() }}"></script>
@endpush
