@extends('layouts.admin')

@section('title', __('messages.guild_war_map') . ' - ' . $event->title)

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/guild_war/css/style.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/guild_war/css/formation.css') }}?v={{ time() }}">
<link rel="stylesheet" href="{{ asset('assets/leaflet/leaflet.css') }}">
<style>
    .info-panel {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.95);
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 1000;
        min-width: 280px;
        border-left: 5px solid #007bff;
    }
    .info-panel h3 {
        margin-top: 0;
        font-size: 18px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 15px;
        color: #333;
        font-weight: bold;
    }
    .info-item {
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .info-label {
        font-weight: bold;
        color: #555;
    }
    .info-value {
        font-weight: bold;
        color: #007bff;
    }
    .pulse-ring {
        border: 3px solid #28a745;
        border-radius: 50%;
        height: 100%;
        width: 100%;
        position: absolute;
        left: 0;
        top: 0;
        animation: pulsate 2s ease-out;
        animation-iteration-count: infinite;
        opacity: 0.0;
    }
    @keyframes pulsate {
        0% {transform: scale(0.1, 0.1); opacity: 0.0;}
        50% {opacity: 1.0;}
        100% {transform: scale(1.2, 1.2); opacity: 0.0;}
    }
    /* Hide some admin styles if needed */
    .leaflet-container {
        background: #1e1e1e;
    }
</style>
@endpush

@section('content')
<div class="guild-war-app">
    <div class="info-panel">
        <h3><i class="fas fa-map-marked-alt mr-2"></i>{{ __('messages.location_info') }}</h3>
        <div class="info-item">
            <span class="info-label">{{ __('messages.team') }}:</span>
            <span class="info-value">{{ $teamInfo['name'] }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">{{ __('messages.captain') }}:</span>
            <span class="info-value">{{ $teamInfo['captain'] }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">{{ __('messages.your_location') }}:</span>
            <button class="btn btn-sm btn-success" onclick="centerOnUser()">
                <i class="fas fa-crosshairs"></i> {{ __('messages.find_me') }}
            </button>
        </div>
        <hr>
        <div class="mt-2">
             <a href="{{ route('events.index') }}" class="btn btn-secondary btn-sm btn-block">
                 <i class="fas fa-arrow-left mr-1"></i> {{ __('messages.back_to_list') }}
             </a>
        </div>
    </div>

    <div id="mapArea" style="width: 1024px; height: 709px; margin: 0 auto; position: relative; border: 1px solid #444; box-shadow: 0 0 20px rgba(0,0,0,0.5);"></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/leaflet/leaflet.js') }}"></script>
<script>
    // Data from Controller
    const userPosition = @json($userPosition);
    const staticMarkers = @json($staticMarkers);
    const mapImageUrl = '/assets/guild_war/images/map.png';

    // Map Constants
    const MAP_WIDTH = 1024;
    const MAP_HEIGHT = 709;

    function storeToLatLng(x, y) {
        return [MAP_HEIGHT - y, x];
    }

    // Initialize Map
    const map = L.map('mapArea', {
        crs: L.CRS.Simple,
        minZoom: -1,
        maxZoom: 1,
        zoomSnap: 0.1,
        zoomControl: true,
        attributionControl: false
    });

    const bounds = [[0, 0], [MAP_HEIGHT, MAP_WIDTH]];
    L.imageOverlay(mapImageUrl, bounds).addTo(map);
    map.fitBounds(bounds);

    // Render Static Markers
    function renderStaticMarker(type, item) {
        let iconHtml = '';
        let className = '';
        let iconSize = [32, 32];
        let iconAnchor = [16, 16];

        switch(type) {
            case 'objectives':
                className = 'objective-marker';
                iconHtml = `<div class="objective-number">${item.id.split('-').pop()}</div>`;
                break;
            case 'bosses':
                className = 'boss-marker';
                iconHtml = `<img src="/assets/guild_war/images/boss.png" alt="Boss">`;
                break;
            case 'blueTowers':
                className = 'tower-marker blue-tower';
                iconHtml = `<img src="/assets/guild_war/images/tower_blue.png" alt="Blue Tower">`;
                break;
            case 'redTowers':
                className = 'tower-marker red-tower';
                iconHtml = `<img src="/assets/guild_war/images/tower_red.png" alt="Red Tower">`;
                break;
            case 'blueTrees':
                className = 'tree-marker blue-tree';
                iconHtml = `<img src="/assets/guild_war/images/tree_blue.png" alt="Blue Tree">`;
                break;
            case 'redTrees':
                className = 'tree-marker red-tree';
                iconHtml = `<img src="/assets/guild_war/images/tree_red.png" alt="Red Tree">`;
                break;
            case 'blueGeese':
                className = 'goose-marker blue-goose';
                iconHtml = `<img src="/assets/guild_war/images/goose_blue.png" alt="Blue Goose">`;
                break;
            case 'redGeese':
                className = 'goose-marker red-goose';
                iconHtml = `<img src="/assets/guild_war/images/goose_red.png" alt="Red Goose">`;
                break;
            case 'enemies':
                className = 'group-marker enemy-group';
                iconHtml = `<div class="group-number">${item.count || 5}</div>`;
                break;
        }

        const latlng = storeToLatLng(item.x, item.y);
        L.marker(latlng, {
            icon: L.divIcon({
                className: className,
                html: iconHtml,
                iconSize: iconSize,
                iconAnchor: iconAnchor
            }),
            interactive: false
        }).addTo(map);
    }

    // Render All Static Markers
    if (staticMarkers) {
        Object.keys(staticMarkers).forEach(type => {
            if (Array.isArray(staticMarkers[type])) {
                staticMarkers[type].forEach(item => renderStaticMarker(type, item));
            }
        });
    }

    // Render User Drawings
    const userDrawings = @json($userDrawings ?? []);
    if (userDrawings && userDrawings.length > 0) {
         userDrawings.forEach(pathData => {
            const latlngs = pathData.points.map(p => storeToLatLng(p.x, p.y));
            L.polyline(latlngs, {
                color: pathData.color || '#ff0000',
                weight: pathData.width || 3,
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(map);

            // Add arrow head if type is arrow
            if (pathData.type === 'arrow' && latlngs.length > 1) {
                const last = latlngs[latlngs.length - 1];
                const prev = latlngs[latlngs.length - 2];
                // latlngs are arrays [lat, lng]
                const dy = last[0] - prev[0];
                const dx = last[1] - prev[1];
                const angle = Math.atan2(dy, dx) * 180 / Math.PI;
                const rotation = -angle;
                const size = 16;
                const color = pathData.color || '#ff0000';

                const arrowIcon = L.divIcon({
                    className: 'arrow-icon',
                    html: `<div style="transform: rotate(${rotation}deg); transform-origin: center;">
                        <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" style="display: block;">
                            <path d="M2,2 L${size},${size/2} L2,${size-2} z" fill="${color}" />
                        </svg>
                    </div>`,
                    iconSize: [size, size],
                    iconAnchor: [size/2, size/2]
                });

                L.marker(last, {icon: arrowIcon, interactive: false}).addTo(map);
            }
         });
    }

    // Render User Route (Legacy?)
    if (userPosition && userPosition.route && userPosition.route.length > 1) {
        // Convert route points to LatLngs
        const routeLatLngs = userPosition.route.map(p => storeToLatLng(p.x, p.y));

        // Draw Polyline
        L.polyline(routeLatLngs, {
            color: '#f39c12',
            weight: 3,
            opacity: 0.8,
            dashArray: '10, 5',
            lineCap: 'round',
            lineJoin: 'round'
        }).addTo(map);

        // Draw Arrows
        for (let i = 0; i < routeLatLngs.length - 1; i++) {
            const p1 = routeLatLngs[i];
            const p2 = routeLatLngs[i+1];

            const dLat = p2[0] - p1[0];
            const dLng = p2[1] - p1[1];

            // Calculate angle
            let angle = Math.atan2(dLat, dLng) * 180 / Math.PI;

            const arrowIcon = L.divIcon({
                className: 'route-arrow',
                html: `<div style="transform: rotate(${-angle}deg); color: #e67e22; font-size: 24px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">➤</div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            const midLat = (p1[0] + p2[0]) / 2;
            const midLng = (p1[1] + p2[1]) / 2;

            L.marker([midLat, midLng], {icon: arrowIcon, interactive: false}).addTo(map);
        }
    }

    // Render User Marker
    if (userPosition) {
        const latlng = storeToLatLng(userPosition.x, userPosition.y);

        let html = '';
        let className = '';

        if (userPosition.is_group) {
            // Render as Group/Team
            className = 'group-marker';
            html = `<div class="group-number" style="background: #28a745; color: white; border: 2px solid white;">YOU</div>`;
        } else {
            // Render as Member
            let role = userPosition.role || 'DPS';
            // Map role names to CSS classes if needed
            if (role === 'Tanker') role = 'Tank';

            className = `member-marker role-${role}`;
            // Use same HTML structure as admin map but forced visibility for tooltip
            html = `
                <div class="marker-tooltip" style="display: block; opacity: 1; visibility: visible; top: -50px; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,0.8); color: white; padding: 5px 10px; border-radius: 4px; white-space: nowrap; font-size: 12px; pointer-events: none;">
                    <div class="tooltip-name" style="font-weight: bold; color: #4ade80;">YOU</div>
                    <div class="tooltip-info">${userPosition.name}</div>
                </div>
            `;
        }

        // Add highlight circle (Pulse)
        const pulseIcon = L.divIcon({
            className: 'pulse-icon',
            html: '<div class="pulse-ring"></div>',
            iconSize: [60, 60],
            iconAnchor: [30, 30]
        });
        L.marker(latlng, { icon: pulseIcon, zIndexOffset: 900, interactive: false }).addTo(map);

        // Add the actual marker
        L.marker(latlng, {
            icon: L.divIcon({
                className: className,
                html: html,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            }),
            zIndexOffset: 1000,
            interactive: false
        }).addTo(map);
    }

    window.centerOnUser = function() {
        if (userPosition) {
            const latlng = storeToLatLng(userPosition.x, userPosition.y);
            map.setView(latlng, 0);
        }
    };

    // Auto center on load
    setTimeout(centerOnUser, 500);

</script>
@endpush
