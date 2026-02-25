
// ============================================================================
// LEAFLET MAP INTEGRATION
// ============================================================================

// Global variables
var map;
var leafletMarkers = {}; // Map of ID -> Leaflet Layer
var drawingMode = false;
var leafletDrawingPoints = [];
var leafletDrawingPolyline = null;

// Map constants
const MAP_WIDTH = 1024;
const MAP_HEIGHT = 709;

// Coordinate conversion
function storeToLatLng(x, y) {
    return [MAP_HEIGHT - y, x];
}

function latLngToStore(latlng) {
    return {
        x: latlng.lng,
        y: MAP_HEIGHT - latlng.lat
    };
}

// Initialize Map
function initLeafletMap() {
    // Check if mapArea exists
    let mapArea = document.getElementById('mapArea');
    if (!mapArea) return;

    // Clear existing content and remove listeners instead of replacing the node
    // This ensures app.js retains a valid reference to mapArea
    mapArea.innerHTML = '';

    // Remove listeners added by app.js if possible
    // Note: This relies on app.js functions being global or accessible
    if (window.handleDragOver) mapArea.removeEventListener('dragover', window.handleDragOver);
    if (window.handleDragLeave) mapArea.removeEventListener('dragleave', window.handleDragLeave);
    if (window.handleDrop) mapArea.removeEventListener('drop', window.handleDrop);
    if (window.handleMapClick) mapArea.removeEventListener('click', window.handleMapClick);

    // Initialize map
    map = L.map('mapArea', {
        crs: L.CRS.Simple,
        zoomSnap: 0, // Allow fractional zoom for perfect fitting
        zoomControl: false, // Disable zoom control
        attributionControl: false,
        dragging: false, // Disable panning
        touchZoom: false, // Disable touch zoom
        doubleClickZoom: false, // Disable double click zoom
        scrollWheelZoom: false, // Disable scroll wheel zoom
        boxZoom: false, // Disable box zoom
        keyboard: false // Disable keyboard navigation
    });

    // Add image overlay
    const bounds = [[0, 0], [MAP_HEIGHT, MAP_WIDTH]];
    const imageUrl = window.mapImageUrl || '/assets/guild_war/images/map.png';
    L.imageOverlay(imageUrl, bounds).addTo(map);

    // Fit bounds initially
    map.fitBounds(bounds, { padding: [0, 0], animate: false });

    // Initialize Drawing Layer Group
    window.drawingLayerGroup = L.layerGroup().addTo(map);

    // Override app.js functions first so event listeners use the new versions
    overrideAppFunctions();

    // Setup events
    setupLeafletEvents();

    // Setup Resize Observer for robust resizing
    if (window.ResizeObserver) {
        const resizeObserver = new ResizeObserver(() => {
            if (map) {
                map.invalidateSize();
                // Re-fit bounds to keep the map fully visible and centered
                map.fitBounds(bounds, { padding: [0, 0], animate: false });
            }
        });
        const mapContainer = document.getElementById('mapArea');
        if (mapContainer) resizeObserver.observe(mapContainer);
    } else {
        // Fallback for older browsers
        window.addEventListener('resize', function() {
            if (map) {
                map.invalidateSize();
                map.fitBounds(bounds, { padding: [0, 0], animate: false });
            }
        });
    }

    console.log("Leaflet Map Initialized");

    // Initial Render
    setTimeout(() => {
        window.renderMap();
    }, 100);
}

function setupLeafletEvents() {
    // Drawing Events
    map.on('mousedown', function(e) {
        if (!drawingMode) return;
        map.dragging.disable();
        leafletDrawingPoints = [];
        const latlng = e.latlng;
        const storePos = latLngToStore(latlng);
        leafletDrawingPoints.push(storePos);

        leafletDrawingPolyline = L.polyline([latlng], {
            color: drawingColor || '#ff0000',
            weight: 3
        }).addTo(map);
    });

    map.on('mousemove', function(e) {
        if (!drawingMode || !leafletDrawingPolyline) return;
        const latlng = e.latlng;
        const storePos = latLngToStore(latlng);
        leafletDrawingPoints.push(storePos);
        leafletDrawingPolyline.addLatLng(latlng);
    });

    map.on('mouseup', function(e) {
        if (!drawingMode || !leafletDrawingPolyline) return;

        if (leafletDrawingPoints.length > 1) {
            const pathData = {
                points: [...leafletDrawingPoints],
                timestamp: Date.now(),
                color: drawingColor || '#ff0000',
                width: 3
            };

            drawingPaths.push(pathData);

            if (!autoDeleteDrawings) {
                const stateCopy = drawingPaths.map(path => ({
                    points: [...path.points],
                    timestamp: path.timestamp,
                    color: path.color,
                    width: path.width
                }));
                drawingHistory.push(stateCopy);
                drawingRedoStack = [];
                updateUndoRedoButtons();
            }

            if (autoDeleteDrawings) {
                schedulePathDeletion(drawingPaths.length - 1);
            }
        }

        map.removeLayer(leafletDrawingPolyline);
        leafletDrawingPolyline = null;
        leafletDrawingPoints = [];
        window.redrawAllPaths();
    });

    // Click Events (for placing items)
    map.on('click', function(e) {
        if (!window.placingMode) return;

        const latlng = e.latlng;
        const storePos = latLngToStore(latlng);
        const x = storePos.x;
        const y = storePos.y;

        // Ensure coordinates are within bounds
        if (x < 0 || x > MAP_WIDTH || y < 0 || y > MAP_HEIGHT) return;

        if (window.placingMode === 'objective') window.placeObjectiveMarker(x, y);
        else if (window.placingMode === 'boss') window.placeBossMarker(x, y);
        else if (window.placingMode === 'blue-tower') window.placeBlueTowerMarker(x, y);
        else if (window.placingMode === 'red-tower') window.placeRedTowerMarker(x, y);
        else if (window.placingMode === 'blue-tree') window.placeBlueTreeMarker(x, y);
        else if (window.placingMode === 'red-tree') window.placeRedTreeMarker(x, y);
        else if (window.placingMode === 'blue-goose') window.placeBlueGooseMarker(x, y);
        else if (window.placingMode === 'red-goose') window.placeRedGooseMarker(x, y);

        // Reset placing mode if needed, or keep it for multiple placements?
        // app.js keeps it active until toggled off.
    });

    // Drop Events
    const mapContainer = map.getContainer();
    mapContainer.addEventListener('dragover', window.handleDragOver);
    mapContainer.addEventListener('dragleave', window.handleDragLeave);
    mapContainer.addEventListener('drop', window.handleDrop);
}

// Helper to create Leaflet marker
function createLeafletMarker(id, x, y, options) {
    const latlng = storeToLatLng(x, y);
    const icon = L.divIcon({
        className: options.className,
        html: options.html,
        iconSize: options.iconSize || [32, 32],
        iconAnchor: options.iconAnchor || [16, 16]
    });

    const marker = L.marker(latlng, {
        icon: icon,
        draggable: true
    }).addTo(map);

    marker.id = id;
    leafletMarkers[id] = marker;

    marker.on('dragend', function(e) {
        const newLatLng = marker.getLatLng();
        const storePos = latLngToStore(newLatLng);

        // Clamp coordinates
        const x = Math.max(0, Math.min(MAP_WIDTH, storePos.x));
        const y = Math.max(0, Math.min(MAP_HEIGHT, storePos.y));

        if (options.onDragEnd) {
            options.onDragEnd(x, y);
        }
    });

    return marker;
}

// ============================================================================
// APP FUNCTION OVERRIDES
// ============================================================================

function overrideAppFunctions() {

    // --- Drag & Drop Overrides ---
    window.handleDragOver = function(e) {
        e.preventDefault();
        e.stopPropagation();
        const mapArea = document.getElementById('mapArea');
        if (mapArea) mapArea.classList.add('drag-over');
    };

    window.handleDragLeave = function(e) {
        e.preventDefault();
        e.stopPropagation();
        const mapArea = document.getElementById('mapArea');
        if (mapArea && e.target === mapArea) {
            mapArea.classList.remove('drag-over');
        }
    };

    window.handleDrop = function(e) {
        e.preventDefault();
        e.stopPropagation();
        const mapArea = document.getElementById('mapArea');
        if (mapArea) mapArea.classList.remove('drag-over');

        let dragData;
        try {
            dragData = JSON.parse(e.dataTransfer.getData('text/plain'));
        } catch (err) {
            console.warn('Invalid drag data', err);
            return;
        }

        if (!dragData || !dragData.type) return;

        const latlng = map.mouseEventToLatLng(e);
        const storePos = latLngToStore(latlng);

        // Clamp coordinates to map bounds to prevent placing items in the void/padding
        let x = Math.max(0, Math.min(MAP_WIDTH, storePos.x));
        let y = Math.max(0, Math.min(MAP_HEIGHT, storePos.y));

        const { type, data } = dragData;

        if (type === 'toolbar-item') {
            const itemType = data;
            if (itemType === 'objective') window.placeObjectiveMarker(x, y);
            else if (itemType === 'boss') window.placeBossMarker(x, y);
            else if (itemType === 'blue-tower') window.placeBlueTowerMarker(x, y);
            else if (itemType === 'red-tower') window.placeRedTowerMarker(x, y);
            else if (itemType === 'blue-tree') window.placeBlueTreeMarker(x, y);
            else if (itemType === 'red-tree') window.placeRedTreeMarker(x, y);
            else if (itemType === 'blue-goose') window.placeBlueGooseMarker(x, y);
            else if (itemType === 'red-goose') window.placeRedGooseMarker(x, y);
        } else if (type === 'team') {
            // Offsets removed from app.js and leaflet_map.js, passing raw coordinates
            window.placeTeamGroupOnMap(data, x, y);
        } else if (type === 'member') {
            const member = members.find(m => m.id === parseInt(data));
            if (member && !window.isPlayerPlaced(member.id)) {
                if (window.getTotalPlacedPlayers() >= MAX_PLAYERS) {
                    alert(`Maximum ${MAX_PLAYERS} players allowed!`);
                    return;
                }
                window.placeMemberOnMap(member, x, y);
            } else if (member) {
                alert(`${member.name} is already placed!`);
            }
        } else if (type === 'split-member') {
            window.splitMemberFromGroup(dragData.groupId, parseInt(dragData.memberId));
            // The split places it near group, but we want it at drop location
            const placedMember = placedMembers.find(m => m.memberId === parseInt(dragData.memberId));
            if (placedMember) {
                placedMember.x = x;
                placedMember.y = y;
                window.renderMap();
                savePositions();
            }
        }
    };

    // --- Drawing System Overrides ---
    window.initializeCanvas = function() {
        // Disable default canvas listeners by replacing it
        const drawingCanvas = document.getElementById('drawingCanvas');
        if (drawingCanvas) {
            const newCanvas = drawingCanvas.cloneNode(true);
            drawingCanvas.parentNode.replaceChild(newCanvas, drawingCanvas);
            newCanvas.style.display = 'none';
        }
    };

    window.toggleDrawingMode = function() {
        if (drawingMode) {
            drawingMode = false;
            drawBtn.classList.remove('active');
            document.getElementById('mapArea').classList.remove('drawing-mode');
            if (map) {
                map.dragging.enable();
                map.getContainer().style.cursor = '';
            }
        } else {
            drawingMode = true;
            placingMode = null;
            drawBtn.classList.add('active');

            // Deactivate other toolbar buttons
            document.querySelectorAll('.toolbar-btn').forEach(b => b.classList.remove('active'));

            document.getElementById('mapArea').classList.remove('placing-mode');
            document.getElementById('mapArea').classList.add('drawing-mode');
            if (map) {
                map.dragging.disable();
                map.getContainer().style.cursor = 'crosshair';
            }
        }
    };

    window.redrawAllPaths = function() {
        if (!window.drawingLayerGroup) window.drawingLayerGroup = L.layerGroup().addTo(map);
        window.drawingLayerGroup.clearLayers();

        drawingPaths.forEach(pathData => {
            if (!pathData) return;
            const latlngs = pathData.points.map(p => storeToLatLng(p.x, p.y));
            L.polyline(latlngs, {
                color: pathData.color,
                weight: pathData.width,
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(window.drawingLayerGroup);
        });
    };

    window.clearAllDrawings = async function() {
        if (drawingPaths.length === 0) return;
        const confirmed = await showConfirm('Clear All Drawings', 'Are you sure you want to clear all drawings?');
        if (confirmed) {
            if (!autoDeleteDrawings && drawingPaths.length > 0) {
                const stateCopy = drawingPaths.map(path => ({
                    points: [...path.points],
                    timestamp: path.timestamp,
                    color: path.color,
                    width: path.width
                }));
                drawingHistory.push(stateCopy);
            }
            drawingPaths = [];
            drawingDeleteTimers.forEach(timer => clearTimeout(timer));
            drawingDeleteTimers = [];
            window.redrawAllPaths();
            updateUndoRedoButtons();
        }
    };

    window.resizeCanvas = function() {
        if (map) map.invalidateSize();
    };

    // --- Map Rendering Override ---
    window.renderMap = function() {
        console.log("Leaflet renderMap");

        // Clear Markers
        Object.values(leafletMarkers).forEach(layer => map.removeLayer(layer));
        leafletMarkers = {};

        // Render Members
        if (typeof placedMembers !== 'undefined') {
            placedMembers.forEach(p => {
                const member = members.find(m => m.id === p.memberId);
                if (member) window.placeMemberOnMap(member, p.x, p.y, true);
            });
        }

        // Render Groups
        if (typeof placedGroups !== 'undefined') placedGroups.forEach(g => window.renderGroupMarker(g));

        // Render Objects
        if (typeof placedObjectives !== 'undefined') placedObjectives.forEach(o => window.placeObjectiveMarker(o.x, o.y, o.id));
        if (typeof placedBosses !== 'undefined') placedBosses.forEach(b => window.placeBossMarker(b.x, b.y, b.id));
        if (typeof placedBlueTowers !== 'undefined') placedBlueTowers.forEach(t => window.placeBlueTowerMarker(t.x, t.y, t.id));
        if (typeof placedRedTowers !== 'undefined') placedRedTowers.forEach(t => window.placeRedTowerMarker(t.x, t.y, t.id));
        if (typeof placedBlueTrees !== 'undefined') placedBlueTrees.forEach(t => window.placeBlueTreeMarker(t.x, t.y, t.id));
        if (typeof placedRedTrees !== 'undefined') placedRedTrees.forEach(t => window.placeRedTreeMarker(t.x, t.y, t.id));
        if (typeof placedBlueGeese !== 'undefined') placedBlueGeese.forEach(g => window.placeBlueGooseMarker(g.x, g.y, g.id));
        if (typeof placedRedGeese !== 'undefined') placedRedGeese.forEach(g => window.placeRedGooseMarker(g.x, g.y, g.id));
        if (typeof placedEnemies !== 'undefined') placedEnemies.forEach(e => window.placeEnemyGroup(e.x, e.y, e.id, e.count));

        // Render Drawings
        window.redrawAllPaths();

        updatePlaceholder();
        if (window.updateEnemyCount) window.updateEnemyCount();
    };

    // --- Placement & Removal Overrides ---

    // Boss
    window.placeBossMarker = function(x, y, existingId = null) {
        const id = existingId || `boss-${Date.now()}-${Math.floor(Math.random()*1000)}`;
        createLeafletMarker(id, x, y, {
            className: 'boss-marker',
            html: `<img src="/assets/guild_war/images/boss.png" alt="Boss" draggable="false"><button class="remove-btn" onclick="removeBossMarker('${id}')">×</button>`,
            iconSize: [56, 56],
            iconAnchor: [28, 28],
            onDragEnd: (nx, ny) => {
                const item = placedBosses.find(b => b.id === id);
                if (item) { item.x = nx; item.y = ny; savePositions(); }
            }
        });
        if (!existingId) {
            placedBosses.push({ id: id, x: x, y: y });
            savePositions();
            updatePlaceholder();
        }
    };
    window.removeBossMarker = function(id) {
        if (leafletMarkers[id]) { map.removeLayer(leafletMarkers[id]); delete leafletMarkers[id]; }
        placedBosses = placedBosses.filter(b => b.id !== id);
        savePositions();
        updatePlaceholder();
    };

    // Objective
    window.placeObjectiveMarker = function(x, y, existingId = null) {
        const id = existingId || `objective-${Date.now()}-${Math.floor(Math.random()*1000)}`;
        createLeafletMarker(id, x, y, {
            className: 'objective-marker',
            html: `<button class="remove-btn" onclick="removeObjectiveMarker('${id}')">×</button>`,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            onDragEnd: (nx, ny) => {
                const item = placedObjectives.find(o => o.id === id);
                if (item) { item.x = nx; item.y = ny; savePositions(); }
            }
        });
        if (!existingId) {
            placedObjectives.push({ id: id, x: x, y: y });
            savePositions();
            updatePlaceholder();
        }
    };
    window.removeObjectiveMarker = function(id) {
        if (leafletMarkers[id]) { map.removeLayer(leafletMarkers[id]); delete leafletMarkers[id]; }
        placedObjectives = placedObjectives.filter(o => o.id !== id);
        savePositions();
        updatePlaceholder();
    };

    // Towers/Trees/Geese Helper
    const placeGeneric = (x, y, type, array, removeFunc, existingId = null, w=40, h=40) => {
        const id = existingId || `${type}-${Date.now()}-${Math.floor(Math.random()*1000)}`;
        // Extract base type (e.g. 'blue-tower' -> 'tower_blue')
        const parts = type.split('-');
        const color = parts[0];
        const kind = parts[1];
        const imgName = `${kind}_${color}`;

        createLeafletMarker(id, x, y, {
            className: `${kind}-marker`,
            html: `<img src="/assets/guild_war/images/${imgName}.png" alt="${type}" draggable="false"><button class="remove-btn" onclick="${removeFunc}('${id}')">×</button>`,
            iconSize: [w, h],
            iconAnchor: [w/2, h/2], // Center anchor roughly
            onDragEnd: (nx, ny) => {
                const item = array.find(i => i.id === id);
                if (item) { item.x = nx; item.y = ny; savePositions(); }
            }
        });
        if (!existingId) {
            array.push({ id: id, x: x, y: y });
            savePositions();
            updatePlaceholder();
        }
    };

    window.placeBlueTowerMarker = (x, y, id) => placeGeneric(x, y, 'blue-tower', placedBlueTowers, 'removeTowerMarker', id, 40, 60);
    window.placeRedTowerMarker = (x, y, id) => placeGeneric(x, y, 'red-tower', placedRedTowers, 'removeTowerMarker', id, 40, 60);
    window.removeTowerMarker = function(id) {
        if (leafletMarkers[id]) { map.removeLayer(leafletMarkers[id]); delete leafletMarkers[id]; }
        if (id.includes('blue')) placedBlueTowers = placedBlueTowers.filter(t => t.id !== id);
        else placedRedTowers = placedRedTowers.filter(t => t.id !== id);
        savePositions();
        updatePlaceholder();
    };

    window.placeBlueTreeMarker = (x, y, id) => placeGeneric(x, y, 'blue-tree', placedBlueTrees, 'removeTreeMarker', id, 40, 50);
    window.placeRedTreeMarker = (x, y, id) => placeGeneric(x, y, 'red-tree', placedRedTrees, 'removeTreeMarker', id, 40, 50);
    window.removeTreeMarker = function(id) {
        if (leafletMarkers[id]) { map.removeLayer(leafletMarkers[id]); delete leafletMarkers[id]; }
        if (id.includes('blue')) placedBlueTrees = placedBlueTrees.filter(t => t.id !== id);
        else placedRedTrees = placedRedTrees.filter(t => t.id !== id);
        savePositions();
        updatePlaceholder();
    };

    window.placeBlueGooseMarker = (x, y, id) => placeGeneric(x, y, 'blue-goose', placedBlueGeese, 'removeGooseMarker', id, 40, 40);
    window.placeRedGooseMarker = (x, y, id) => placeGeneric(x, y, 'red-goose', placedRedGeese, 'removeGooseMarker', id, 40, 40);
    window.removeGooseMarker = function(id) {
        if (leafletMarkers[id]) { map.removeLayer(leafletMarkers[id]); delete leafletMarkers[id]; }
        if (id.includes('blue')) placedBlueGeese = placedBlueGeese.filter(t => t.id !== id);
        else placedRedGeese = placedRedGeese.filter(t => t.id !== id);
        savePositions();
        updatePlaceholder();
    };

    // Enemy Group
    window.placeEnemyGroup = function(x, y, existingId = null, existingCount = null) {
        const id = existingId || `enemy-group-${Date.now()}-${Math.floor(Math.random()*1000)}`;
        const count = existingCount || ENEMIES_PER_CLICK;
        createLeafletMarker(id, x, y, {
            className: 'group-marker enemy-group',
            html: `
                <div class="group-number">${count}</div>
                <div class="group-tooltip">
                    <div class="tooltip-header">Enemy Group</div>
                    <div class="tooltip-info">${count} Enemy Players</div>
                </div>
                <button class="remove-btn" onclick="removeEnemyGroup('${id}')">×</button>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            onDragEnd: (nx, ny) => {
                const item = placedEnemies.find(i => i.id === id);
                if (item) { item.x = nx; item.y = ny; savePositions(); }
            }
        });
        if (!existingId) {
            placedEnemies.push({ id: id, x: x, y: y, count: count });
            savePositions();
            updatePlaceholder();
            if (window.updateEnemyCount) window.updateEnemyCount();
        }
    };
    window.removeEnemyGroup = function(id) {
        if (leafletMarkers[id]) { map.removeLayer(leafletMarkers[id]); delete leafletMarkers[id]; }
        placedEnemies = placedEnemies.filter(e => e.id !== id);
        savePositions();
        updatePlaceholder();
        if (window.updateEnemyCount) window.updateEnemyCount();
    };

    // Member Marker
    window.placeMemberOnMap = function(member, x, y, isRenderOnly = false) {
        const markerId = `member-${member.id}`;
        if (leafletMarkers[markerId]) map.removeLayer(leafletMarkers[markerId]);

        const displayTeamName = getTeamDisplayName(member.team);
        const html = `
            <div class="marker-tooltip">
                <div class="tooltip-name">${member.name}</div>
                <div class="tooltip-weapons">
                    ${member.weapon1 ? `<div>⚔️ ${member.weapon1}</div>` : ''}
                    ${member.weapon2 ? `<div>🛡️ ${member.weapon2}</div>` : ''}
                </div>
                <div class="tooltip-info">${member.role} | ${displayTeamName}</div>
            </div>
            <button class="remove-btn" onclick="removeMemberMarker(${member.id})">×</button>
        `;

        createLeafletMarker(markerId, x, y, {
            className: `member-marker role-${member.role}`,
            html: html,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            onDragEnd: (nx, ny) => {
                const placement = placedMembers.find(p => p.memberId === member.id);
                if (placement) {
                    placement.x = nx;
                    placement.y = ny;
                    savePositions();
                }
            }
        });

        if (!isRenderOnly) {
            if (!placedMembers.find(p => p.memberId === member.id)) {
                placedMembers.push({ memberId: member.id, x: x, y: y });
            }
            updateGroupsAfterMemberPlacement(member.id);
            savePositions();
            updateCounts();
            updatePlaceholder();
            renderMemberList();
        }
    };

    window.highlightMapMarker = function(memberId) {
        // Remove highlight from all markers
        Object.values(leafletMarkers).forEach(layer => {
            if (layer.getElement()) {
                layer.getElement().classList.remove('highlighted-marker');
                layer.setZIndexOffset(0);
            }
        });

        const markerId = `member-${memberId}`;
        const marker = leafletMarkers[markerId];
        if (marker && marker.getElement()) {
            marker.getElement().classList.add('highlighted-marker');
            marker.setZIndexOffset(1000);

            // Pan to marker if offscreen? Maybe not needed unless requested.
            // map.panTo(marker.getLatLng());
        }
    };

    // Inject CSS for highlighting
    const style = document.createElement('style');
    style.innerHTML = `
        .highlighted-marker {
            z-index: 1000 !important;
        }
        .highlighted-marker::before {
            transform: scale(1.2);
            box-shadow: 0 0 12px yellow;
            border-color: yellow !important;
        }
        .member-item.placed-member {
            opacity: 0.6;
            background-color: #f0f0f0;
            cursor: pointer !important; /* Allow click */
        }
        .member-item.highlighted {
            background-color: #e6f7ff;
            border: 1px solid #1890ff;
            font-weight: bold;
        }
    `;
    document.head.appendChild(style);
    window.removeMemberMarker = function(memberId) {
        const markerId = `member-${memberId}`;
        if (leafletMarkers[markerId]) { map.removeLayer(leafletMarkers[markerId]); delete leafletMarkers[markerId]; }
        placedMembers = placedMembers.filter(p => p.memberId !== memberId);
        savePositions();
        updateCounts();
        updatePlaceholder();
        renderMemberList();
    };

    // Group Marker
    window.renderGroupMarker = function(group) {
        const markerId = `group-${group.id}`;
        if (leafletMarkers[markerId]) map.removeLayer(leafletMarkers[markerId]);

        const marker = createLeafletMarker(markerId, group.x, group.y, {
            className: 'group-marker',
            html: '<div class="group-loading">...</div>',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            onDragEnd: (nx, ny) => {
                group.x = nx;
                group.y = ny;
                checkAndMergeNearbyGroups(group);
                savePositions();
            }
        });

        const tempDiv = document.createElement('div');
        updateGroupMarker(tempDiv, group);
        const element = marker.getElement();
        if (element) element.innerHTML = tempDiv.innerHTML;
    };
    window.removeGroupMarker = function(groupId) {
        const markerId = `group-${groupId}`;
        if (leafletMarkers[markerId]) { map.removeLayer(leafletMarkers[markerId]); delete leafletMarkers[markerId]; }
        placedGroups = placedGroups.filter(g => g.id !== groupId);
        savePositions();
        updateCounts();
        updatePlaceholder();
        renderMemberList();
    };

    // Group Logic
    window.checkAndMergeNearbyGroups = function(movedGroup) {
        for (const otherGroup of placedGroups) {
            if (otherGroup.id !== movedGroup.id) {
                const distance = Math.sqrt(
                    Math.pow(movedGroup.x - otherGroup.x, 2) +
                    Math.pow(movedGroup.y - otherGroup.y, 2)
                );

                if (distance < GROUP_MERGE_DISTANCE) {
                    otherGroup.teams.push(...movedGroup.teams.filter(t => !otherGroup.teams.includes(t)));
                    otherGroup.memberIds.push(...movedGroup.memberIds);
                    window.removeGroupMarker(movedGroup.id);
                    window.renderGroupMarker(otherGroup);
                    savePositions();
                    updateCounts();
                    break;
                }
            }
        }
    };

    window.mergeGroups = function(existingGroup, newTeamName, newMembers) {
        if (!existingGroup.teams.includes(newTeamName)) {
            existingGroup.teams.push(newTeamName);
        }
        const newMemberIds = newMembers.map(m => m.id);
        existingGroup.memberIds.push(...newMemberIds);
        window.renderGroupMarker(existingGroup);
        savePositions();
        updateCounts();
    };

    window.createNewGroup = function(teamName, teamMembers, x, y) {
        const groupId = `group-${Date.now()}`;
        const memberIds = teamMembers.map(m => m.id);
        const group = {
            id: groupId,
            teams: [teamName],
            memberIds: memberIds,
            x: x,
            y: y
        };
        placedGroups.push(group);
        window.renderGroupMarker(group);
        savePositions();
        updateCounts();
        updatePlaceholder();
    };

    window.placeTeamGroupOnMap = function(teamName, x, y) {
        const teamMembers = members.filter(m => m.team === teamName && !isPlayerPlaced(m.id));
        if (teamMembers.length === 0) {
            alert(`All players from ${teamName} are already placed!`);
            return;
        }
        if (window.getTotalPlacedPlayers() + teamMembers.length > MAX_PLAYERS) {
            alert(`Cannot place ${teamName}: would exceed max players!`);
            return;
        }

        const nearbyGroup = findNearbyGroup(x, y);
        if (nearbyGroup) {
            window.mergeGroups(nearbyGroup, teamName, teamMembers);
        } else {
            window.createNewGroup(teamName, teamMembers, x, y);
        }
        renderMemberList();
    };

    window.splitMemberFromGroup = function(groupId, memberId) {
        const group = placedGroups.find(g => g.id === groupId);
        if (!group) return;
        const member = members.find(m => m.id === memberId);
        if (!member) return;

        group.memberIds = group.memberIds.filter(id => id !== memberId);
        if (group.memberIds.length === 0) {
            window.removeGroupMarker(groupId);
        } else {
            window.renderGroupMarker(group);
        }
        window.placeMemberOnMap(member, group.x + 50, group.y);
    };

    window.splitGroup = function(groupId) {
        const group = placedGroups.find(g => g.id === groupId);
        if (!group || group.teams.length <= 1) return;

        window.removeGroupMarker(groupId);
        const baseX = group.x;
        const baseY = group.y;
        const offset = 45;

        group.teams.forEach((teamName, index) => {
            const teamMemberIds = group.memberIds.filter(id => {
                const member = members.find(m => m.id === id);
                return member && member.team === teamName;
            });

            if (teamMemberIds.length > 0) {
                const angle = (index / group.teams.length) * 2 * Math.PI;
                const newGroup = {
                    id: `group-${Date.now()}-${index}`,
                    teams: [teamName],
                    memberIds: teamMemberIds,
                    x: baseX + Math.cos(angle) * offset,
                    y: baseY + Math.sin(angle) * offset
                };
                placedGroups.push(newGroup);
                window.renderGroupMarker(newGroup);
            }
        });
        savePositions();
        updateCounts();
    };

    window.clearAllPlacements = async function() {
        if (window.getTotalPlacedPlayers() === 0 && placedObjectives.length === 0 && placedBosses.length === 0 && placedEnemies.length === 0 && placedBlueTowers.length === 0) return;

        const confirmed = await showConfirm('Clear All Placements', 'Remove all items?');
        if (confirmed) {
            Object.values(leafletMarkers).forEach(layer => map.removeLayer(layer));
            leafletMarkers = {};
            placedMembers = [];
            placedGroups = [];
            placedObjectives = [];
            placedBosses = [];
            placedBlueTowers = [];
            placedRedTowers = [];
            placedBlueTrees = [];
            placedRedTrees = [];
            placedBlueGeese = [];
            placedRedGeese = [];
            placedEnemies = [];

            savePositions();
            updateCounts();
            updatePlaceholder();
            renderMemberList();
            if (window.updateEnemyCount) window.updateEnemyCount();
        }
    };
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLeafletMap);
} else {
    initLeafletMap();
}
