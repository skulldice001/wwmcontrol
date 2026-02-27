@extends('layouts.admin')

@section('title', 'Bản đồ cá nhân - ' . $event->title)

@push('styles')
<style>
    .personal-map-container {
        position: relative;
        width: 100%;
        height: 800px; /* Match typical admin height */
        border: 2px solid #333;
        border-radius: 8px;
        overflow: hidden;
        background-color: #2c3e50;
        background-image: url('{{ asset('assets/guild_war/images/map.png') }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .user-marker {
        position: absolute;
        width: 32px;
        height: 32px;
        background-color: #ff0000;
        border: 2px solid #fff;
        border-radius: 50%;
        transform: translate(-50%, -50%); /* Center the marker on the coordinates */
        z-index: 10;
        box-shadow: 0 0 10px rgba(0,0,0,0.5);
        animation: pulse 2s infinite;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
    }

    .user-marker::after {
        content: "YOU";
        position: absolute;
        top: -20px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        white-space: nowrap;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(255, 0, 0, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(255, 0, 0, 0);
        }
    }

    .info-panel {
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .info-item {
        margin-bottom: 5px;
        font-size: 1.1em;
    }

    .info-label {
        font-weight: bold;
        color: #555;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <a href="{{ route('events.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.back_to_events') }}
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">{{ $event->title }}</h5>
                    <div class="mb-3">
                        <span class="badge badge-success p-2" style="font-size: 1rem;">
                            {{ $teamName }}
                        </span>
                    </div>
                    <div class="text-muted">
                        {{ __('messages.team_captain') }}
                    </div>
                    <span class="font-weight-bold">{{ $captainName }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> {{ __('messages.map_responsive_note', ['default' => 'Map adapts to screen size.']) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- OpenSeadragon Container -->
            <div id="openseadragon-viewer" style="width: 100%; height: 800px; background-color: #000; border: 1px solid #ccc; border-radius: 8px;"></div>
        </div>
    </div>
</div>

@php
    // Get image dimensions
    $imagePath = public_path('assets/guild_war/images/map.png');
    $imageWidth = 1024;
    $imageHeight = 709;
    
    if (file_exists($imagePath)) {
        $info = getimagesize($imagePath);
        $imageWidth = $info[0];
        $imageHeight = $info[1];
    }

    // Admin View Dimensions (Assumed from CSS/Layout)
    // We assume the admin view was 1600x800 and used background-size: cover
    $adminWidth = 1600;
    $adminHeight = 800;
    
    // Calculate Scaling and Cropping to map Admin Coordinates to Image Coordinates
    // background-size: cover means the image is scaled to cover the container
    // Scale factor is max(containerWidth/imageWidth, containerHeight/imageHeight)
    $scaleX = $adminWidth / $imageWidth;
    $scaleY = $adminHeight / $imageHeight;
    $scale = max($scaleX, $scaleY);
    
    // Dimensions of the image after scaling
    $scaledWidth = $imageWidth * $scale;
    $scaledHeight = $imageHeight * $scale;
    
    // Calculate crop (centering)
    // The top-left of the admin container (0,0) corresponds to some point in the scaled image
    // If scaledWidth > adminWidth, x offset is (scaledWidth - adminWidth) / 2
    // If scaledHeight > adminHeight, y offset is (scaledHeight - adminHeight) / 2
    $cropX = ($scaledWidth - $adminWidth) / 2;
    $cropY = ($scaledHeight - $adminHeight) / 2;
    
    // Convert User Position (Admin Coordinates) to Image Coordinates (Pixels)
    // userX_admin -> userX_scaled = userX_admin + cropX
    // userX_image = userX_scaled / scale
    $userImageX = ($userPosition['x'] + $cropX) / $scale;
    $userImageY = ($userPosition['y'] + $cropY) / $scale;
@endphp

@push('scripts')
<!-- OpenSeadragon (Local) -->
<script src="{{ asset('assets/openseadragon/openseadragon-bin-4.1.0/openseadragon.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var viewer = OpenSeadragon({
            id: "openseadragon-viewer",
            prefixUrl: "{{ asset('assets/openseadragon/openseadragon-bin-4.1.0/images/') }}/",
            tileSources: {
                type: 'image',
                url: "{{ asset('assets/guild_war/images/map.png') }}"
            },
            showNavigator: true,
            defaultZoomLevel: 1,
            minZoomLevel: 0.5,
            maxZoomLevel: 10,
            visibilityRatio: 1,
            constrainDuringPan: true
        });

        // Add User Marker Overlay
        // We use an HTML element for the marker
        var marker = document.createElement("div");
        marker.id = "user-marker";
        marker.className = "user-marker-overlay";
        marker.innerHTML = '<i class="fas fa-user"></i>';

        // Styling for the marker
        marker.style.width = "24px";
        marker.style.height = "24px";
        marker.style.backgroundColor = "#e74c3c";
        marker.style.border = "2px solid white";
        marker.style.borderRadius = "50%";
        marker.style.display = "flex";
        marker.style.alignItems = "center";
        marker.style.justifyContent = "center";
        marker.style.color = "white";
        marker.style.boxShadow = "0 0 10px rgba(0,0,0,0.5)";
        marker.style.animation = "pulse 2s infinite";
        marker.style.cursor = "pointer";
        
        // Add tooltip or click handler if needed
        marker.title = "{{ __('messages.you_are_here') ?? 'You are here' }}";

        // Coordinates in Image Pixel Space
        var imageX = {{ $userImageX }};
        var imageY = {{ $userImageY }};
        var imageWidth = {{ $imageWidth }};
        var imageHeight = {{ $imageHeight }};

        // OpenSeadragon uses normalized coordinates (0 to 1 for width)
        // Point(x, y) where x is 0..1, y is 0..aspectRatio
        var viewportX = imageX / imageWidth;
        var viewportY = imageY / imageWidth; // Note: Divide by WIDTH to maintain aspect ratio in OSD coordinate system

        viewer.addHandler('open', function() {
            viewer.addOverlay({
                element: marker,
                location: new OpenSeadragon.Point(viewportX, viewportY),
                placement: OpenSeadragon.Placement.CENTER
            });
            
            // Zoom to marker initially
            // viewer.viewport.zoomTo(2, new OpenSeadragon.Point(viewportX, viewportY));
        });
    });
</script>

<style>
@keyframes pulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); }
    70% { transform: scale(1.2); box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); }
}
</style>
@endpush
@endsection
