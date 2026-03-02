// ============================================================================
// APPLICATION STATE & CONFIGURATION
// ============================================================================

var members = window.initialMembers || [];

// Placed items on map
var placedMembers = [];
var placedGroups = [];
var placedObjectives = [];
var placedBosses = [];
var placedBlueTowers = [];
var placedRedTowers = [];
var placedBlueTrees = [];
var placedRedTrees = [];
var placedBlueGeese = [];
var placedRedGeese = [];
var placedEnemies = [];

// UI State
var filteredMembers = [...members];
var currentFilter = 'all';
var currentRoleFilter = 'all';
var currentView = 'grouped'; // 'grouped' or 'list'
var placingMode = null; // 'objective' or 'boss' or 'blue-tower' or 'red-tower' or 'blue-tree' or 'red-tree' or 'blue-goose' or 'red-goose' or null

// Drawing State
var drawingMode = false;
var autoDeleteDrawings = false;
var drawingPaths = [];
var drawingDeleteTimers = [];
var drawingHistory = [];
var drawingRedoStack = [];
var drawingColor = '#ff0000'; // Default red color

// Split UI State
var activeSplitGroupId = null;

// Constants
const MAX_PLAYERS = 30;
const MAX_ENEMIES = 30;
const ENEMIES_PER_CLICK = 5;
const GROUP_MERGE_DISTANCE = 80;
const AUTO_DELETE_DELAY = 10000;
// Team Configuration
let teams = [];
// Removed TEAM_ORDER constant as it is replaced by dynamic teams array

// Custom team name mappings (Backwards compatibility)
let teamNameMappings = {};

// Load custom team names from localStorage
function loadTeamNames() {
    const saved = localStorage.getItem('vcross-gvg-team-names');
    if (saved) {
        try {
            let mappings = JSON.parse(saved);
            // Migrate old team names in mappings
            const migrationMap = {
                'FrontLine': 'Team 1',
                'Jungle': 'Team 2',
                'Defence 1': 'Team 3',
                'Defence 2': 'Team 4',
                'Backline 1': 'Team 5',
                'Backline 2': 'Team 6'
            };

            // Convert old keys to new keys
            const newMappings = {};
            for (const [oldKey, value] of Object.entries(mappings)) {
                const newKey = migrationMap[oldKey] || oldKey;
                newMappings[newKey] = value;
            }

            teamNameMappings = newMappings;
            // Save migrated mappings
            if (Object.keys(migrationMap).some(old => old in mappings)) {
                saveTeamNames();
            }
        } catch (e) {
            console.error('Error loading team names:', e);
            teamNameMappings = {};
        }
    }
}

// Save team names to localStorage
function saveTeamNames() {
    localStorage.setItem('vcross-gvg-team-names', JSON.stringify(teamNameMappings));
}

// Get display name for team (custom or default)
function getTeamDisplayName(teamId) {
    // Check dynamic teams first
    const team = teams.find(t => t.id === teamId);
    if (team) return team.name;

    // Fallback to mappings or ID
    return teamNameMappings[teamId] || teamId;
}

// Add new team
async function addTeam() {
    const name = await showPrompt(GW_CONSTANTS.PROMPTS.CREATE_TEAM_TITLE, GW_CONSTANTS.PROMPTS.CREATE_TEAM_MSG, GW_CONSTANTS.PROMPTS.CREATE_TEAM_DEFAULT);
    if (name) {
        const id = `team_${Date.now()}`;
        teams.push({
            id: id,
            name: name,
            captainId: null,
            description: ''
        });
        savePositions(); // Save structure
        renderMemberList();
    }
}

// Delete team
async function deleteTeam(teamId) {
    const team = teams.find(t => t.id === teamId);
    if (!team) return;

    // Check if team has members
    const hasMembers = members.some(m => m.team === teamId);
    if (hasMembers) {
        alert(GW_CONSTANTS.ALERTS.CANNOT_DELETE_HAS_MEMBERS.replace('{name}', team.name));
        return;
    }

    if (await showConfirm(GW_CONSTANTS.PROMPTS.DELETE_TEAM_TITLE, GW_CONSTANTS.PROMPTS.DELETE_TEAM_MSG.replace('{name}', team.name))) {
        teams = teams.filter(t => t.id !== teamId);
        savePositions();
        renderMemberList();
    }
}

// Rename team
async function renameTeam(teamId) {
    const team = teams.find(t => t.id === teamId);
    if (team) {
        const newName = await showPrompt(GW_CONSTANTS.PROMPTS.RENAME_TEAM_TITLE, GW_CONSTANTS.PROMPTS.RENAME_TEAM_MSG.replace('{name}', team.name), team.name);
        if (newName && newName !== team.name) {
            team.name = newName;
            savePositions();
            renderMemberList();
        }
    } else {
        // Fallback for legacy
        const currentName = getTeamDisplayName(teamId);
        const newName = await showPrompt(
            GW_CONSTANTS.PROMPTS.RENAME_TEAM_TITLE,
            GW_CONSTANTS.PROMPTS.RENAME_TEAM_MSG.replace('{name}', currentName),
            currentName
        );

        if (newName !== null && newName !== '') {
            teamNameMappings[teamId] = newName;
            saveTeamNames();
            renderMemberList();
        }
    }
}

// Edit team mission/description
async function editTeamDescription(teamId) {
    const team = teams.find(t => t.id === teamId);
    if (!team) {
        return;
    }

    const currentDescription = team.description || '';
    const newDescription = await showPrompt(
        GW_CONSTANTS.PROMPTS.TEAM_MISSION_TITLE,
        GW_CONSTANTS.PROMPTS.TEAM_MISSION_MSG.replace('{name}', team.name),
        currentDescription
    );

    if (newDescription !== null) {
        team.description = newDescription;
        savePositions();
        renderMemberList();
    }
}

// Toggle Captain
function toggleCaptain(memberId, teamId) {
    const team = teams.find(t => t.id === teamId);
    if (!team) return;

    if (team.captainId === memberId) {
        team.captainId = null; // Unassign
    } else {
        team.captainId = memberId; // Assign new (replaces old)
    }
    savePositions();
    renderMemberList();
}


// ============================================================================
// DOM ELEMENT REFERENCES
// ============================================================================

let memberList, mapArea, searchInput, roleFilterButtons, viewToggleButtons;
let clearMapBtn, exportBtn, importBtn, importFileInput, playerCount, placedCount;
let addObjectiveBtn, addBossBtn, addBlueTowerBtn, addRedTowerBtn;
let addBlueTreeBtn, addRedTreeBtn, addBlueGooseBtn, addRedGooseBtn;
let drawBtn, drawPathBtn, clearDrawBtn, undoDrawBtn, redoDrawBtn, autoDeleteToggle, drawColorPicker;
let drawingCanvas, ctx, addEnemiesBtn, enemyCount;
let managePlayersBtn, playerManagementModal, playerEditModal, closeModalBtn;
let closeEditModalBtn, addNewPlayerBtn, playerManagementList, playerEditForm;
let cancelEditBtn, themeToggleBtn;
let confirmModal, confirmModalTitle, confirmModalMessage, confirmOkBtn, confirmCancelBtn;
let promptModal, promptModalTitle, promptModalMessage, promptModalInput, promptOkBtn, promptCancelBtn;
let hotkeyHelpModal, closeHotkeyModalBtn;

// ============================================================================
// CUSTOM CONFIRM DIALOG
// ============================================================================

let isPromptActive = false;

function showConfirm(title, message) {
    if (isPromptActive) return Promise.resolve(false);
    isPromptActive = true;
    return new Promise((resolve) => {
        let isResolved = false;
        confirmModalTitle.textContent = title;
        confirmModalMessage.textContent = message;
        confirmModal.style.display = 'flex';

        const handleOk = () => {
            if (isResolved) return;
            isResolved = true;
            cleanup();
            resolve(true);
        };

        const handleCancel = () => {
            if (isResolved) return;
            isResolved = true;
            cleanup();
            resolve(false);
        };

        const cleanup = () => {
            isPromptActive = false;
            confirmModal.style.display = 'none';
            confirmOkBtn.removeEventListener('click', handleOk);
            confirmCancelBtn.removeEventListener('click', handleCancel);
        };

        confirmOkBtn.addEventListener('click', handleOk);
        confirmCancelBtn.addEventListener('click', handleCancel);
    });
}

function showPrompt(title, message, defaultValue = '') {
    if (isPromptActive) return Promise.resolve(null);
    isPromptActive = true;
    return new Promise((resolve) => {
        let isResolved = false;
        promptModalTitle.textContent = title;
        promptModalMessage.textContent = message;
        promptModalInput.value = defaultValue;
        promptModal.style.display = 'flex';

        // Focus and select the input
        setTimeout(() => {
            promptModalInput.focus();
            promptModalInput.select();
        }, 100);

        const handleOk = () => {
            if (isResolved) return;
            isResolved = true;
            const value = promptModalInput.value.trim();
            cleanup();
            resolve(value || null);
        };

        const handleCancel = () => {
            if (isResolved) return;
            isResolved = true;
            cleanup();
            resolve(null);
        };

        const handleEnter = (e) => {
            if (e.key === 'Enter') {
                handleOk();
            } else if (e.key === 'Escape') {
                handleCancel();
            }
        };

        const cleanup = () => {
            isPromptActive = false;
            promptModal.style.display = 'none';
            promptOkBtn.removeEventListener('click', handleOk);
            promptCancelBtn.removeEventListener('click', handleCancel);
            promptModalInput.removeEventListener('keydown', handleEnter);
        };

        promptOkBtn.addEventListener('click', handleOk);
        promptCancelBtn.addEventListener('click', handleCancel);
        promptModalInput.addEventListener('keydown', handleEnter);
    });
}

// ============================================================================
// INITIALIZATION
// ============================================================================

function initializeDOM() {
    memberList = document.getElementById('memberList');
    mapArea = document.getElementById('mapArea');
    searchInput = document.getElementById('searchInput');
    roleFilterButtons = document.querySelectorAll('.role-filter-btn');
    viewToggleButtons = document.querySelectorAll('.view-toggle-btn');
    clearMapBtn = document.getElementById('clearMapBtn');
    exportBtn = document.getElementById('exportBtn');
    importBtn = document.getElementById('importBtn');
    importFileInput = document.getElementById('importFileInput');
    playerCount = document.getElementById('playerCount');
    placedCount = document.getElementById('placedCount');
    addObjectiveBtn = document.getElementById('addObjectiveBtn');
    addBossBtn = document.getElementById('addBossBtn');
    addBlueTowerBtn = document.getElementById('addBlueTowerBtn');
    addRedTowerBtn = document.getElementById('addRedTowerBtn');
    addBlueTreeBtn = document.getElementById('addBlueTreeBtn');
    addRedTreeBtn = document.getElementById('addRedTreeBtn');
    addBlueGooseBtn = document.getElementById('addBlueGooseBtn');
    addRedGooseBtn = document.getElementById('addRedGooseBtn');
    drawBtn = document.getElementById('drawBtn');
    drawPathBtn = document.getElementById('drawPathBtn');
    clearDrawBtn = document.getElementById('clearDrawBtn');
    undoDrawBtn = document.getElementById('undoDrawBtn');
    redoDrawBtn = document.getElementById('redoDrawBtn');
    autoDeleteToggle = document.getElementById('autoDeleteToggle');
    drawColorPicker = document.getElementById('drawColorPicker');
    drawingCanvas = document.getElementById('drawingCanvas');
    if (drawingCanvas) ctx = drawingCanvas.getContext('2d');
    addEnemiesBtn = document.getElementById('addEnemiesBtn');
    enemyCount = document.getElementById('enemyCount');
    managePlayersBtn = document.getElementById('managePlayersBtn');
    playerManagementModal = document.getElementById('playerManagementModal');
    playerEditModal = document.getElementById('playerEditModal');
    closeModalBtn = document.getElementById('closeModalBtn');
    closeEditModalBtn = document.getElementById('closeEditModalBtn');
    addNewPlayerBtn = document.getElementById('addNewPlayerBtn');
    playerManagementList = document.getElementById('playerManagementList');
    playerEditForm = document.getElementById('playerEditForm');
    cancelEditBtn = document.getElementById('cancelEditBtn');
    themeToggleBtn = document.getElementById('themeToggleBtn');
    confirmModal = document.getElementById('confirmModal');
    confirmModalTitle = document.getElementById('confirmModalTitle');
    confirmModalMessage = document.getElementById('confirmModalMessage');
    confirmOkBtn = document.getElementById('confirmOkBtn');
    confirmCancelBtn = document.getElementById('confirmCancelBtn');
    promptModal = document.getElementById('promptModal');
    promptModalTitle = document.getElementById('promptModalTitle');
    promptModalMessage = document.getElementById('promptModalMessage');
    promptModalInput = document.getElementById('promptModalInput');
    promptOkBtn = document.getElementById('promptOkBtn');
    promptCancelBtn = document.getElementById('promptCancelBtn');
    hotkeyHelpModal = document.getElementById('hotkeyHelpModal');
    closeHotkeyModalBtn = document.getElementById('closeHotkeyModalBtn');
    formationNameInput = document.getElementById('formationNameInput');
    loadFormationSelect = document.getElementById('loadFormationSelect');
}

function init() {
    initializeDOM();
    loadPlayersFromStorage();
    loadTeamNames();
    loadThemePreference();
    renderMemberList();
    setupEventListeners();
    loadSavedPositions();
    initLoadFormationSelect();
    updateCounts();
    initializeCanvas();
    setupClickOutsideHandler();
    setupPlayerManagementHandlers();
}
// Initialize the load formation select dropdown
function initLoadFormationSelect() {
    if (!loadFormationSelect || !window.pastEvents || !Array.isArray(window.pastEvents)) return;

    window.pastEvents.forEach(event => {
        const option = document.createElement('option');
        option.value = event.id;
        // Format date simply
        const dateObj = new Date(event.start_time);
        const dateStr = dateObj.toLocaleDateString();
        option.textContent = `${event.title} (${dateStr})`;
        loadFormationSelect.appendChild(option);
    });

    loadFormationSelect.addEventListener('change', async function() {
        const eventId = this.value;
        if (!eventId) return;

        const event = window.pastEvents.find(e => e.id == eventId);
        if (event && event.formation_data) {
            const confirmed = await showConfirm(
                GW_CONSTANTS.PROMPTS.LOAD_FORMATION_TITLE,
                GW_CONSTANTS.ALERTS.LOAD_FORMATION_CONFIRM.replace('{title}', event.title)
            );

            if (confirmed) {
                loadSavedPositions(event.formation_data);
                // Reset select to default after loading
                this.value = '';
            } else {
                this.value = '';
            }
        }
    });
}

// ============================================================================
// MEMBER LIST RENDERING
// ============================================================================

// Render member list
function renderMemberList() {
    memberList.innerHTML = '';

    if (currentView === 'grouped') {
        renderGroupedView();
    } else {
        renderListView();
    }
}

// Add members to team (Called from Vue component)
window.addMembersToTeam = function(teamId, memberIds) {
    if (!teamId || !memberIds || !Array.isArray(memberIds)) return;
    
    let changed = false;
    memberIds.forEach(id => {
        const member = members.find(m => m.id === id);
        if (member) {
            member.team = teamId;
            changed = true;
        }
    });
    
    if (changed) {
        savePositions();
        renderMemberList();
    }
};

window.openAddMemberPopup = function(teamId) {
    const teamIds = teams.map(t => t.id);
    // Find members not in any team or explicitly 'Unassigned'
    // Also include members whose team ID doesn't exist in current teams list (orphaned)
    const unassignedMembers = members.filter(m => 
        !m.team || 
        m.team === 'Unassigned' || 
        !teamIds.includes(m.team)
    );
    
    const team = teams.find(t => t.id === teamId);
    const teamName = team ? team.name : '';

    if (window.openTeamMemberSelector) {
        window.openTeamMemberSelector(teamId, unassignedMembers, teamName);
    } else {
        console.error("Vue component not ready");
        alert("Please wait for the page to fully load.");
    }
};

// Render grouped view by team
function renderGroupedView() {
    // Add "Create Team" button
    const addBtn = document.createElement('button');
    addBtn.className = 'add-team-btn';
    addBtn.innerHTML = GW_CONSTANTS.TEAM_MANAGEMENT.CREATE_NEW_TEAM;
    addBtn.onclick = addTeam;
    memberList.appendChild(addBtn);

    teams.forEach(team => {
        const teamId = team.id;

        // Get all team members first (not filtered yet)
        const allTeamMembers = members.filter(m => m.team === teamId);

        // Then filter out placed members and apply search/role filters
        const teamMembers = allTeamMembers.filter(m => {
            // Apply search filter
            const searchTerm = searchInput.value.toLowerCase();
            const matchesSearch = !searchTerm ||
                                 m.name.toLowerCase().includes(searchTerm) ||
                                 m.role.toLowerCase().includes(searchTerm) ||
                                 getTeamDisplayName(m.team).toLowerCase().includes(searchTerm);
            if (!matchesSearch) return false;

            // Apply role filter
            const matchesRole = currentRoleFilter === 'all' || m.role === currentRoleFilter;
            if (!matchesRole) return false;

            return true;
        });

        // Always render team group if it exists in 'teams' array, even if empty (so we can drag into it)
        const groupDiv = document.createElement('div');
        groupDiv.className = 'team-group';
        groupDiv.dataset.teamId = teamId;

        // Drag events for reordering/assigning
        groupDiv.addEventListener('dragover', handleTeamDragOver);
        groupDiv.addEventListener('dragleave', handleTeamDragLeave);
        groupDiv.addEventListener('drop', handleTeamDrop);

        const headerDiv = document.createElement('div');
        headerDiv.className = 'team-group-header';
        headerDiv.draggable = true;
        headerDiv.dataset.teamId = teamId;
        const displayName = getTeamDisplayName(teamId);
        const teamObj = teams.find(t => t.id === teamId) || {};
        const description = teamObj.description || '';

        headerDiv.innerHTML = `
            <div class="team-header-main">
                <span class="team-name-wrapper">
                    <span class="toggle-icon">▼</span>
                    <span class="team-name">${displayName}</span>
                    <button class="add-member-btn" onclick="event.stopPropagation(); openAddMemberPopup('${teamId}')" title="${GW_CONSTANTS.TEAM_MANAGEMENT.ADD_MEMBERS}"><i class="fas fa-user-plus"></i></button>
                    <button class="rename-team-btn" onclick="event.stopPropagation(); renameTeam('${teamId}')" title="${GW_CONSTANTS.TEAM_MANAGEMENT.RENAME_TEAM}">✏️</button>
                    <button class="delete-team-btn" onclick="event.stopPropagation(); deleteTeam('${teamId}')" title="${GW_CONSTANTS.TEAM_MANAGEMENT.DELETE_TEAM}">×</button>
                </span>
                <span class="team-count">${teamMembers.length}</span>
            </div>
            <div class="team-description ${description ? '' : 'team-description-empty'}"
                 onclick="event.stopPropagation(); editTeamDescription('${teamId}')">
                ${description || GW_CONSTANTS.TEAM_MANAGEMENT.TEAM_DESCRIPTION_PLACEHOLDER}
            </div>
        `;
        headerDiv.addEventListener('click', (e) => {
            if (e.target === headerDiv || e.target.closest('.toggle-icon') || e.target.closest('.team-name')) {
                toggleTeamGroup(groupDiv);
            }
        });
        headerDiv.addEventListener('dragstart', handleTeamDragStart);
        headerDiv.addEventListener('dragend', handleDragEnd);

        const playersDiv = document.createElement('div');
        playersDiv.className = 'team-group-players';

        teamMembers.forEach(member => {
            const memberElement = createMemberElement(member);
            playersDiv.appendChild(memberElement);
        });

        // Add placeholder if empty so we can drop into it
        if (teamMembers.length === 0) {
            playersDiv.innerHTML = `<div class="empty-team-placeholder" style="padding: 10px; text-align: center; color: #aaa; font-size: 0.8rem; font-style: italic;">${GW_CONSTANTS.TEAM_MANAGEMENT.DROP_MEMBERS_HERE}</div>`;
        }

        groupDiv.appendChild(headerDiv);
        groupDiv.appendChild(playersDiv);
        memberList.appendChild(groupDiv);
    });
}

// Render list view (all players)
function renderListView() {
    // Filter members that aren't placed
    const availableMembers = members.filter(m => {
        // Apply search filter
        const searchTerm = searchInput.value.toLowerCase();
        const matchesSearch = !searchTerm ||
                             m.name.toLowerCase().includes(searchTerm) ||
                             m.role.toLowerCase().includes(searchTerm) ||
                             m.team.toLowerCase().includes(searchTerm);
        if (!matchesSearch) return false;

        // Apply role filter
        const matchesRole = currentRoleFilter === 'all' || m.role === currentRoleFilter;
        if (!matchesRole) return false;

        return true;
    });

    availableMembers.forEach(member => {
        const memberElement = createMemberElement(member);
        memberList.appendChild(memberElement);
    });
}

// Toggle team group collapse
function toggleTeamGroup(groupDiv) {
    groupDiv.classList.toggle('collapsed');
}

// Create member element
function createMemberElement(member) {
    const div = document.createElement('div');
    const isPlaced = isPlayerPlaced(member.id);
    div.className = `member-item ${isPlaced ? 'placed-member' : ''}`;
    div.draggable = !isPlaced;
    div.dataset.memberId = member.id;

    const team = teams.find(t => t.id === member.team);
    const isCaptain = team && team.captainId === member.id;
    const captainClass = isCaptain ? 'active' : '';
    const captainIcon = isCaptain ? '👑' : '☆';

    // Only show remove button if member is in a team
    const removeBtn = member.team ? `<button class="remove-from-team-btn" onclick="event.stopPropagation(); removeMemberFromTeam(${member.id})" title="${GW_CONSTANTS.TEAM_MANAGEMENT.REMOVE_FROM_TEAM}" style="background: none; border: none; color: #e53e3e; cursor: pointer; font-weight: bold;">×</button>` : '';

    div.innerHTML = `
        <div class="member-info">
            <div class="member-name">${member.name}</div>
            <div class="member-team">${getTeamDisplayName(member.team)}</div>
            <div class="member-weapons">
                <div class="weapon-item">${GW_CONSTANTS.TEAM_MANAGEMENT.WEAPON_PREFIX}1: ${member.weapon1 || GW_CONSTANTS.TEAM_MANAGEMENT.NO_WEAPON}</div>
                <div class="weapon-item">${GW_CONSTANTS.TEAM_MANAGEMENT.WEAPON_PREFIX}2: ${member.weapon2 || GW_CONSTANTS.TEAM_MANAGEMENT.NO_WEAPON}</div>
            </div>
        </div>
        <div class="member-controls" style="display: flex; flex-direction: column; align-items: flex-end; gap: 5px;">
            <div class="role-badge">${member.role}</div>
            <div style="display: flex; gap: 5px;">
                <button class="captain-btn ${captainClass}" onclick="event.stopPropagation(); toggleCaptain(${member.id}, '${member.team}')" title="${GW_CONSTANTS.TEAM_MANAGEMENT.TOGGLE_CAPTAIN}">${captainIcon}</button>
                ${removeBtn}
            </div>
        </div>
    `;

    if (!isPlaced) {
        div.addEventListener('dragstart', handleDragStart);
        div.addEventListener('dragend', handleDragEnd);
    }

    div.addEventListener('click', (e) => {
        // Remove highlight from all other items
        document.querySelectorAll('.member-item').forEach(el => el.classList.remove('highlighted'));
        div.classList.add('highlighted');

        // Update selected member ID
        window.selectedMemberId = member.id;

        // Highlight on map
        if (window.highlightMapMarker) {
            window.highlightMapMarker(member.id);
        }

        // Redraw paths to show only this member's path
        if (window.redrawAllPaths) {
            window.redrawAllPaths();
        }
    });

    return div;
}

// ============================================================================
// EVENT HANDLERS & SETUP
// ============================================================================

// Setup event listeners
function setupEventListeners() {
    console.log('Setting up event listeners...');

    // Helper to safely add listener
    const addSafeListener = (element, event, handler) => {
        if (element) {
            element.addEventListener(event, handler);
        } else {
            // Log warning but don't spam for known optional elements if any
            // console.warn(`Element for ${event} listener not found.`);
        }
    };

    // Menu dropdown toggle
    const menuBtn = document.getElementById('menuBtn');
    const menuContent = document.getElementById('menuContent');

    if (menuBtn && menuContent) {
        menuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            menuContent.classList.toggle('show');
        });
    }

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (menuContent && !e.target.closest('.menu-dropdown')) {
            menuContent.classList.remove('show');
        }
    });

    // Map area drop events
    if (mapArea) {
        mapArea.addEventListener('dragover', handleDragOver);
        mapArea.addEventListener('dragleave', handleDragLeave);
        mapArea.addEventListener('drop', handleDrop);
        mapArea.addEventListener('click', handleMapClick);
    } else {
        console.error('Map area element not found!');
    }

    // Search functionality
    addSafeListener(searchInput, 'input', handleSearch);

    // View toggle buttons
    if (viewToggleButtons) {
        viewToggleButtons.forEach(btn => {
            btn.addEventListener('click', handleViewToggle);
        });
    }

    // Role filter buttons
    if (roleFilterButtons) {
        roleFilterButtons.forEach(btn => {
            btn.addEventListener('click', handleRoleFilter);
        });
    }

    // Objective and Boss buttons
    addSafeListener(addObjectiveBtn, 'click', toggleObjectiveMode);
    addSafeListener(addBossBtn, 'click', toggleBossMode);
    addSafeListener(addBlueTowerBtn, 'click', toggleBlueTowerMode);
    addSafeListener(addRedTowerBtn, 'click', toggleRedTowerMode);
    addSafeListener(addBlueTreeBtn, 'click', toggleBlueTreeMode);
    addSafeListener(addRedTreeBtn, 'click', toggleRedTreeMode);
    addSafeListener(addBlueGooseBtn, 'click', toggleBlueGooseMode);
    addSafeListener(addRedGooseBtn, 'click', toggleRedGooseMode);

    // Toolbar drag events
    const toolbarButtons = document.querySelectorAll('.toolbar-btn[draggable="true"]');
    console.log('Found draggable toolbar buttons:', toolbarButtons.length);
    toolbarButtons.forEach(btn => {
        btn.addEventListener('dragstart', handleToolbarDragStart);
        btn.addEventListener('dragend', handleToolbarDragEnd);
    });

    // Drawing buttons
    addSafeListener(drawBtn, 'click', toggleDrawingMode);
    addSafeListener(drawPathBtn, 'click', () => { if (window.togglePolyLineDrawingMode) window.togglePolyLineDrawingMode(); });
    addSafeListener(clearDrawBtn, 'click', clearAllDrawings);
    addSafeListener(undoDrawBtn, 'click', () => { if (window.undoDrawing) window.undoDrawing(); });
    addSafeListener(redoDrawBtn, 'click', () => { if (window.redoDrawing) window.redoDrawing(); });
    addSafeListener(autoDeleteToggle, 'change', handleAutoDeleteToggle);
    if (drawColorPicker) {
        drawColorPicker.addEventListener('change', (e) => {
            drawingColor = e.target.value;
        });
    }

    // Enemy button
    addSafeListener(addEnemiesBtn, 'click', addEnemies);

    // Save Formation Button (Top)
    const saveFormationBtn = document.getElementById('saveFormationBtn');
    addSafeListener(saveFormationBtn, 'click', saveFormationToServer);

    // Save Formation Button (Bottom)
    const saveFormationBtnBottom = document.getElementById('saveFormationBtnBottom');
    addSafeListener(saveFormationBtnBottom, 'click', saveFormationToServer);

    // Clear map button
    addSafeListener(clearMapBtn, 'click', clearAllPlacements);

    // Export button
    addSafeListener(exportBtn, 'click', exportPositions);

    // Import button
    addSafeListener(importBtn, 'click', importPositions);
    addSafeListener(importFileInput, 'change', handleImportFile);

    // Hot Key button
    const hotKeyBtn = document.getElementById('hotKeyBtn');
    addSafeListener(hotKeyBtn, 'click', showHotkeyHelp);

    // Theme toggle button
    addSafeListener(themeToggleBtn, 'click', toggleTheme);

    // Window resize
    window.addEventListener('resize', resizeCanvas);

    // Hotkey modal
    addSafeListener(closeHotkeyModalBtn, 'click', closeHotkeyHelp);

    // Hotkey modal click outside to close
    if (hotkeyHelpModal) {
        hotkeyHelpModal.addEventListener('click', (e) => {
            if (e.target === hotkeyHelpModal) {
                closeHotkeyHelp();
            }
        });
    } else {
        console.warn('Hotkey Help Modal not found');
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyboardShortcut);

    console.log('Event listeners setup complete.');
}

// ============================================================================
// KEYBOARD SHORTCUTS
// ============================================================================

function handleKeyboardShortcut(e) {
    // Ignore shortcuts when typing in input fields
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
        return;
    }

    // Shift + ? - Show hotkey help
    if (e.shiftKey && e.key === '?') {
        e.preventDefault();
        showHotkeyHelp();
        return;
    }

    // Escape - Deselect tool
    if (e.key === 'Escape') {
        e.preventDefault();
        deactivatePlacingMode();
        if (drawingMode) {
            toggleDrawingMode();
        }
        return;
    }

    // Ctrl + Z - Undo drawing
    if (e.ctrlKey && e.key === 'z') {
        e.preventDefault();
        if (!autoDeleteDrawings && drawingHistory.length > 0) {
            undoDrawing();
        }
        return;
    }

    // Ctrl + Y - Redo drawing
    if (e.ctrlKey && e.key === 'y') {
        e.preventDefault();
        if (!autoDeleteDrawings && drawingRedoStack.length > 0) {
            redoDrawing();
        }
        return;
    }

    // Single key shortcuts
    const key = e.key.toLowerCase();

    switch(key) {
        case 'o':
            e.preventDefault();
            toggleObjectiveMode();
            break;
        case 'b':
            e.preventDefault();
            toggleBossMode();
            break;
        case '1':
            e.preventDefault();
            toggleBlueTowerMode();
            break;
        case '2':
            e.preventDefault();
            toggleRedTowerMode();
            break;
        case '3':
            e.preventDefault();
            toggleBlueTreeMode();
            break;
        case '4':
            e.preventDefault();
            toggleRedTreeMode();
            break;
        case '5':
            e.preventDefault();
            toggleBlueGooseMode();
            break;
        case '6':
            e.preventDefault();
            toggleRedGooseMode();
            break;
        case 'd':
            e.preventDefault();
            toggleDrawingMode();
            break;
    }
}

function showHotkeyHelp() {
    hotkeyHelpModal.style.display = 'flex';
}

function closeHotkeyHelp() {
    hotkeyHelpModal.style.display = 'none';
}

function deactivatePlacingMode() {
    placingMode = null;
    addObjectiveBtn.classList.remove('active');
    addBossBtn.classList.remove('active');
    addBlueTowerBtn.classList.remove('active');
    addRedTowerBtn.classList.remove('active');
    addBlueTreeBtn.classList.remove('active');
    addRedTreeBtn.classList.remove('active');
    addBlueGooseBtn.classList.remove('active');
    addRedGooseBtn.classList.remove('active');
    mapArea.style.cursor = 'default';
}


// ============================================================================
// DRAG & DROP HANDLERS
// ============================================================================

// Drag handler for toolbar items
function handleToolbarDragStart(e) {
    console.log('Toolbar drag start:', e.currentTarget.dataset.type);
    e.dataTransfer.effectAllowed = 'copy';
    const dragData = {
        type: 'toolbar-item',
        data: e.currentTarget.dataset.type
    };
    e.dataTransfer.setData('text/plain', JSON.stringify(dragData));
    e.currentTarget.style.opacity = '0.5';
}

function handleToolbarDragEnd(e) {
    e.currentTarget.style.opacity = '1';
}

// Drag handlers for member list
function handleDragStart(e) {
    e.currentTarget.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'copy';
    const dragData = {
        type: 'member',
        data: e.currentTarget.dataset.memberId
    };
    e.dataTransfer.setData('text/plain', JSON.stringify(dragData));
}

// Drag handler for split member from group
function handleSplitMemberDragStart(e) {
    e.stopPropagation();
    e.currentTarget.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    const dragData = {
        type: 'split-member',
        memberId: e.currentTarget.dataset.memberId,
        groupId: e.currentTarget.dataset.groupId
    };
    e.dataTransfer.setData('text/plain', JSON.stringify(dragData));
}

// Handle drag over team group
function handleTeamDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
    e.dataTransfer.dropEffect = 'move';
}

// Handle drag leave team group
function handleTeamDragLeave(e) {
    e.currentTarget.classList.remove('drag-over');
}

// Handle drop on team group (assign member to team)
function handleTeamDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');

    const rawData = e.dataTransfer.getData('text/plain');
    let dragData;

    try {
        dragData = JSON.parse(rawData);
    } catch (err) {
        console.warn('Drag data is not JSON', err);
        return;
    }

    if (!dragData || dragData.type !== 'member') {
        return;
    }

    const memberId = parseInt(dragData.data);
    const targetTeamId = e.currentTarget.dataset.teamId;
    const member = members.find(m => m.id === memberId);

    if (member && member.team !== targetTeamId) {
        // Update member's team
        member.team = targetTeamId;

        // If member was a captain of another team, remove that status
        teams.forEach(t => {
            if (t.captainId === memberId) {
                t.captainId = null;
            }
        });

        savePlayersToStorage();
        renderMemberList();

        // Visual feedback
        // alert(`${member.name} moved to ${getTeamDisplayName(targetTeamId)}`);
    }
}

// Handle team group drag
function handleTeamDragStart(e) {
    e.stopPropagation();
    e.currentTarget.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'copy';
    const dragData = {
        type: 'team',
        data: e.currentTarget.dataset.teamId
    };
    e.dataTransfer.setData('text/plain', JSON.stringify(dragData));
}

function handleDragEnd(e) {
    e.currentTarget.classList.remove('dragging');
}

// Map drag over
function handleDragOver(e) {
    e.preventDefault();
    mapArea.classList.add('drag-over');
}

// Map drag leave
function handleDragLeave(e) {
    if (e.target === mapArea) {
        mapArea.classList.remove('drag-over');
    }
}

// Handle drop on map
function handleDrop(e) {
    e.preventDefault();
    mapArea.classList.remove('drag-over');

    const rawData = e.dataTransfer.getData('text/plain');
    console.log('Drop event data:', rawData);
    let dragData;

    try {
        dragData = JSON.parse(rawData);
    } catch (err) {
        // Fallback for simple text/plain if JSON parse fails
        console.warn('Drag data is not JSON, attempting legacy fallback', err);
        return;
    }

    if (!dragData || !dragData.type) {
        console.error('Invalid drag data:', dragData);
        return;
    }

    const { type, data } = dragData;

    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    if (type === 'toolbar-item') {
        const itemType = data;
        if (itemType === 'objective') placeObjectiveMarker(x, y);
        else if (itemType === 'boss') placeBossMarker(x, y);
        else if (itemType === 'blue-tower') placeBlueTowerMarker(x, y);
        else if (itemType === 'red-tower') placeRedTowerMarker(x, y);
        else if (itemType === 'blue-tree') placeBlueTreeMarker(x, y);
        else if (itemType === 'red-tree') placeRedTreeMarker(x, y);
        else if (itemType === 'blue-goose') placeBlueGooseMarker(x, y);
        else if (itemType === 'red-goose') placeRedGooseMarker(x, y);
    } else if (type === 'team') {
        // Dropping a team group
        const teamName = data;
        // Adjust position to top-right of cursor for better visibility
        const adjustedX = x + 21; // 16px radius + 5px offset
        const adjustedY = y - 21;
        placeTeamGroupOnMap(teamName, adjustedX, adjustedY);
    } else if (type === 'member') {
        // Dropping individual member
        const memberId = parseInt(data);
        const member = members.find(m => m.id === memberId);

        if (!member) return;

        // Check if already placed
        if (isPlayerPlaced(memberId)) {
            alert(GW_CONSTANTS.ALERTS.MEMBER_ALREADY_PLACED.replace('{name}', member.name));
            return;
        }

        // Check max players limit
        if (getTotalPlacedPlayers() >= MAX_PLAYERS) {
            alert(GW_CONSTANTS.ALERTS.MAX_PLAYERS_REACHED.replace('{max}', MAX_PLAYERS));
            return;
        }

        placeMemberOnMap(member, x, y);
    } else if (type === 'split-member') {
        // Dropping a member split from a group
        const memberId = parseInt(dragData.memberId);
        const groupId = dragData.groupId;

        splitMemberFromGroup(groupId, memberId);

        // Update position to drop location (splitMemberFromGroup places near group by default)
        // We need to move it to drop location
        const placedMember = placedMembers.find(m => m.memberId === memberId);
        if (placedMember) {
            placedMember.x = x;
            placedMember.y = y;
            const marker = mapArea.querySelector(`[data-member-id="${memberId}"]`);
            if (marker) {
                marker.style.left = `${x - 20}px`;
                marker.style.top = `${y - 20}px`;
            }
            savePositions();
        }
    } else if (type === 'member-marker') {
        const memberId = parseInt(dragData.memberId);
        const placement = placedMembers.find(p => p.memberId === memberId);
        if (placement) {
            placement.x = x;
            placement.y = y;
            const marker = mapArea.querySelector(`[data-member-id="${memberId}"]`);
            if (marker) {
                marker.style.left = `${x - 8}px`; // Center 16px
                marker.style.top = `${y - 8}px`;
            }
            savePositions();
        }
    } else if (type === 'group-marker') {
        const groupId = dragData.groupId;
        const group = placedGroups.find(g => g.id === groupId);
        if (group) {
            group.x = x;
            group.y = y;
            const marker = mapArea.querySelector(`[data-group-id="${groupId}"]`);
            if (marker) {
                marker.style.left = `${x - 16}px`; // Center 32px
                marker.style.top = `${y - 16}px`;
                updateGroupMarker(marker, group);
                checkAndMergeNearbyGroups(group);
            }
            savePositions();
        }
    }
}

// Check if player is already placed (in individual or group)
function isPlayerPlaced(memberId) {
    // Check individual placements
    if (placedMembers.find(p => p.memberId === memberId)) {
        return true;
    }

    // Check group placements
    for (const group of placedGroups) {
        if (group.memberIds.includes(memberId)) {
            return true;
        }
    }

    return false;
}

// Get total placed players count
function getTotalPlacedPlayers() {
    let total = placedMembers.length;
    placedGroups.forEach(group => {
        total += group.memberIds.length;
    });
    return total;
}

// ============================================================================
// MAP PLACEMENT - TEAMS & MEMBERS
// ============================================================================

// Place team group on map
function placeTeamGroupOnMap(teamName, x, y) {
    const teamMembers = members.filter(m => m.team === teamName && !isPlayerPlaced(m.id));

    if (teamMembers.length === 0) {
        alert(GW_CONSTANTS.ALERTS.ALL_PLAYERS_PLACED.replace('{name}', teamName));
        return;
    }

    // Check if any member is already placed (shouldn't happen but double check)
    const alreadyPlaced = teamMembers.filter(m => isPlayerPlaced(m.id));
    if (alreadyPlaced.length > 0) {
        alert(GW_CONSTANTS.ALERTS.SOME_PLAYERS_PLACED.replace('{name}', teamName));
        return;
    }

    // Check max players limit
    if (getTotalPlacedPlayers() + teamMembers.length > MAX_PLAYERS) {
        alert(GW_CONSTANTS.ALERTS.CANNOT_PLACE_EXCEED_LIMIT.replace('{name}', teamName).replace('{max}', MAX_PLAYERS));
        return;
    }

    // Check if this group should merge with nearby groups
    const nearbyGroup = findNearbyGroup(x, y);

    if (nearbyGroup) {
        // Merge with nearby group
        mergeGroups(nearbyGroup, teamName, teamMembers);
    } else {
        // Create new group
        createNewGroup(teamName, teamMembers, x, y);
    }

    renderMemberList(); // Re-render to hide placed members
}

// Find nearby group within merge distance
function findNearbyGroup(x, y) {
    for (const group of placedGroups) {
        const distance = Math.sqrt(Math.pow(group.x - x, 2) + Math.pow(group.y - y, 2));
        if (distance < GROUP_MERGE_DISTANCE) {
            return group;
        }
    }
    return null;
}

// Create new group marker
function createNewGroup(teamName, teamMembers, x, y) {
    const groupId = `group-${Date.now()}`;
    const memberIds = teamMembers.map(m => m.id);

    // Create new group
    const group = {
        id: groupId,
        teams: [teamName],
        memberIds: memberIds,
        x: x,
        y: y
    };

    placedGroups.push(group);
    renderGroupMarker(group);
    savePositions();
    updateCounts();
    updatePlaceholder();
}

// Merge groups
function mergeGroups(existingGroup, newTeamName, newMembers) {
    // Add new team if not already in list
    if (!existingGroup.teams.includes(newTeamName)) {
        existingGroup.teams.push(newTeamName);
    }

    // Add new member IDs
    const newMemberIds = newMembers.map(m => m.id);
    existingGroup.memberIds.push(...newMemberIds);

    // Update the marker
    const marker = mapArea.querySelector(`[data-group-id="${existingGroup.id}"]`);
    if (marker) {
        updateGroupMarker(marker, existingGroup);
    }

    savePositions();
    updateCounts();
}

// Render group marker on map
function renderGroupMarker(group) {
    const marker = document.createElement('div');
    marker.className = 'group-marker';
    marker.dataset.groupId = group.id;
    marker.style.left = `${group.x - 16}px`; // Center the 32px marker
    marker.style.top = `${group.y - 16}px`;
    marker.draggable = true;

    updateGroupMarker(marker, group);

    // Make marker draggable
    marker.addEventListener('dragstart', handleGroupMarkerDragStart);
    marker.addEventListener('dragend', handleGroupMarkerDragEnd);

    mapArea.appendChild(marker);
}

// Update group marker content
function updateGroupMarker(marker, group) {
    const roleCount = countRoles(group.memberIds);
    const groupMembers = group.memberIds.map(id => members.find(m => m.id === id)).filter(m => m);

    // Get display names for teams
    const displayTeamNames = group.teams.map(teamName => getTeamDisplayName(teamName)).join(', ');

    marker.innerHTML = `
        <div class="group-number">${group.memberIds.length}</div>
        <div class="group-tooltip">
            <div class="tooltip-header">${GW_CONSTANTS.TOOLTIPS.GROUP}: ${displayTeamNames}</div>
            <div class="tooltip-roles">
                ${roleCount.Tank > 0 ? `<div class="role-item"><span class="role-dot role-Tank"></span> ${roleCount.Tank} Tank</div>` : ''}
                ${roleCount.DPS > 0 ? `<div class="role-item"><span class="role-dot role-DPS"></span> ${roleCount.DPS} DPS</div>` : ''}
                ${roleCount.Healer > 0 ? `<div class="role-item"><span class="role-dot role-Healer"></span> ${roleCount.Healer} Healer</div>` : ''}
                ${roleCount.Support > 0 ? `<div class="role-item"><span class="role-dot role-Support"></span> ${roleCount.Support} Support</div>` : ''}
            </div>
            <div class="tooltip-actions">
                <button class="split-btn" onclick="toggleSplitView('${group.id}')">${GW_CONSTANTS.TOOLTIPS.SPLIT_MEMBERS}</button>
            </div>
            <div class="split-members" id="split-${group.id}" style="display: none;">
                ${groupMembers.map(member => `
                    <div class="split-member-item"
                         draggable="true"
                         ondragstart="handleSplitMemberDragStart(event)"
                         data-member-id="${member.id}"
                         data-group-id="${group.id}">
                        <span class="role-dot role-${member.role}"></span>
                        <div class="split-member-info">
                            <div><span>${member.name}</span> - <span class="member-role">${member.role}</span></div>
                            <div class="split-member-weapons"><small>⚔️ ${member.weapon1 || 'N/A'} | ${member.weapon2 || 'N/A'}</small></div>
                        </div>
                        <button class="split-remove-btn" onclick="splitMemberFromGroup('${group.id}', ${member.id})">×</button>
                    </div>
                `).join('')}
            </div>
            ${group.teams.length > 1 ? `
                <div class="tooltip-actions">
                    <button class="split-btn" onclick="splitGroup('${group.id}')">📦 Split into Teams</button>
                </div>
            ` : ''}
        </div>
        <button class="remove-btn" onclick="removeGroupMarker('${group.id}')">×</button>
    `;
}

// Count roles in a group
function countRoles(memberIds) {
    const count = { Tank: 0, DPS: 0, Healer: 0, Support: 0 };
    memberIds.forEach(id => {
        const member = members.find(m => m.id === id);
        if (member && count[member.role] !== undefined) {
            count[member.role]++;
        }
    });
    return count;
}

// Handle group marker drag
function handleGroupMarkerDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    const dragData = {
        type: 'group-marker',
        groupId: e.currentTarget.dataset.groupId
    };
    e.dataTransfer.setData('text/plain', JSON.stringify(dragData));
    e.currentTarget.style.opacity = '0.5';
}

function handleGroupMarkerDragEnd(e) {
    e.currentTarget.style.opacity = '1';
}

// Check and merge nearby groups after moving
function checkAndMergeNearbyGroups(movedGroup) {
    for (const otherGroup of placedGroups) {
        if (otherGroup.id !== movedGroup.id) {
            const distance = Math.sqrt(
                Math.pow(movedGroup.x - otherGroup.x, 2) +
                Math.pow(movedGroup.y - otherGroup.y, 2)
            );

            if (distance < GROUP_MERGE_DISTANCE) {
                // Merge the groups
                otherGroup.teams.push(...movedGroup.teams.filter(t => !otherGroup.teams.includes(t)));
                otherGroup.memberIds.push(...movedGroup.memberIds);

                // Remove moved group
                const movedMarker = mapArea.querySelector(`[data-group-id="${movedGroup.id}"]`);
                if (movedMarker) movedMarker.remove();

                placedGroups = placedGroups.filter(g => g.id !== movedGroup.id);

                // Update the other group marker
                const otherMarker = mapArea.querySelector(`[data-group-id="${otherGroup.id}"]`);
                if (otherMarker) {
                    updateGroupMarker(otherMarker, otherGroup);
                }

                savePositions();
                updateCounts();
                break;
            }
        }
    }
}

// Remove group marker
function removeGroupMarker(groupId) {
    const marker = mapArea.querySelector(`[data-group-id="${groupId}"]`);
    if (marker) {
        marker.remove();
    }
    placedGroups = placedGroups.filter(g => g.id !== groupId);
    savePositions();
    updateCounts();
    updatePlaceholder();
    renderMemberList(); // Re-render to show members again
}

// Update groups after individual member placement
function updateGroupsAfterMemberPlacement(memberId) {
    placedGroups.forEach(group => {
        const index = group.memberIds.indexOf(memberId);
        if (index > -1) {
            // Remove member from group
            group.memberIds.splice(index, 1);

            // Update the group marker display
            const marker = mapArea.querySelector(`[data-group-id="${group.id}"]`);
            if (marker) {
                if (group.memberIds.length === 0) {
                    // Remove empty group
                    marker.remove();
                    placedGroups = placedGroups.filter(g => g.id !== group.id);
                } else {
                    // Update group number
                    updateGroupMarker(marker, group);
                }
            }
        }
    });
}

// Toggle objective placing mode
function toggleObjectiveMode() {
    if (placingMode === 'objective') {
        // Deactivate
        placingMode = null;
        addObjectiveBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode');
    } else {
        // Activate objective mode
        placingMode = 'objective';
        drawingMode = false;
        if (typeof window.polyLineDrawingMode !== 'undefined' && window.polyLineDrawingMode) window.togglePolyLineDrawingMode();
        addObjectiveBtn.classList.add('active');
        addBossBtn.classList.remove('active');
        addBlueTowerBtn.classList.remove('active');
        addRedTowerBtn.classList.remove('active');
        addBlueTreeBtn.classList.remove('active');
        addRedTreeBtn.classList.remove('active');
        addBlueGooseBtn.classList.remove('active');
        addRedGooseBtn.classList.remove('active');
        drawBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode', 'drawing-mode');
        mapArea.classList.add('placing-mode');
        drawingCanvas.classList.remove('active');
    }
}

// Toggle boss placing mode
function toggleBossMode() {
    if (placingMode === 'boss') {
        // Deactivate
        placingMode = null;
        addBossBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode');
    } else {
        // Activate boss mode
        placingMode = 'boss';
        drawingMode = false;
        if (typeof window.polyLineDrawingMode !== 'undefined' && window.polyLineDrawingMode) window.togglePolyLineDrawingMode();
        addBossBtn.classList.add('active');
        addObjectiveBtn.classList.remove('active');
        addBlueTowerBtn.classList.remove('active');
        addRedTowerBtn.classList.remove('active');
        addBlueTreeBtn.classList.remove('active');
        addRedTreeBtn.classList.remove('active');
        addBlueGooseBtn.classList.remove('active');
        addRedGooseBtn.classList.remove('active');
        drawBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode', 'drawing-mode');
        mapArea.classList.add('placing-mode');
        drawingCanvas.classList.remove('active');
    }
}

// Toggle tower placing mode
// Toggle blue tower placing mode
function toggleBlueTowerMode() {
    if (placingMode === 'blue-tower') {
        // Deactivate
        placingMode = null;
        addBlueTowerBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode');
    } else {
        // Activate blue tower mode
        placingMode = 'blue-tower';
        drawingMode = false;
        if (typeof window.polyLineDrawingMode !== 'undefined' && window.polyLineDrawingMode) window.togglePolyLineDrawingMode();
        addBlueTowerBtn.classList.add('active');
        addObjectiveBtn.classList.remove('active');
        addBossBtn.classList.remove('active');
        addRedTowerBtn.classList.remove('active');
        addBlueTreeBtn.classList.remove('active');
        addRedTreeBtn.classList.remove('active');
        addBlueGooseBtn.classList.remove('active');
        addRedGooseBtn.classList.remove('active');
        drawBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode', 'drawing-mode');
        mapArea.classList.add('placing-mode');
        drawingCanvas.classList.remove('active');
    }
}

// Toggle red tower placing mode
function toggleRedTowerMode() {
    if (placingMode === 'red-tower') {
        // Deactivate
        placingMode = null;
        addRedTowerBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode');
    } else {
        // Activate red tower mode
        placingMode = 'red-tower';
        drawingMode = false;
        if (typeof window.polyLineDrawingMode !== 'undefined' && window.polyLineDrawingMode) window.togglePolyLineDrawingMode();
        addRedTowerBtn.classList.add('active');
        addObjectiveBtn.classList.remove('active');
        addBossBtn.classList.remove('active');
        addBlueTowerBtn.classList.remove('active');
        addBlueTreeBtn.classList.remove('active');
        addRedTreeBtn.classList.remove('active');
        addBlueGooseBtn.classList.remove('active');
        addRedGooseBtn.classList.remove('active');
        drawBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode', 'drawing-mode');
        mapArea.classList.add('placing-mode');
        drawingCanvas.classList.remove('active');
    }
}

// Toggle blue tree placing mode
function toggleBlueTreeMode() {
    if (placingMode === 'blue-tree') {
        // Deactivate
        placingMode = null;
        addBlueTreeBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode');
    } else {
        // Activate blue tree mode
        placingMode = 'blue-tree';
        drawingMode = false;
        if (typeof window.polyLineDrawingMode !== 'undefined' && window.polyLineDrawingMode) window.togglePolyLineDrawingMode();
        addBlueTreeBtn.classList.add('active');
        addObjectiveBtn.classList.remove('active');
        addBossBtn.classList.remove('active');
        addBlueTowerBtn.classList.remove('active');
        addRedTowerBtn.classList.remove('active');
        addRedTreeBtn.classList.remove('active');
        addBlueGooseBtn.classList.remove('active');
        addRedGooseBtn.classList.remove('active');
        drawBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode', 'drawing-mode');
        mapArea.classList.add('placing-mode');
        drawingCanvas.classList.remove('active');
    }
}

// Toggle red tree placing mode
function toggleRedTreeMode() {
    if (placingMode === 'red-tree') {
        // Deactivate
        placingMode = null;
        addRedTreeBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode');
    } else {
        // Activate red tree mode
        placingMode = 'red-tree';
        drawingMode = false;
        if (typeof window.polyLineDrawingMode !== 'undefined' && window.polyLineDrawingMode) window.togglePolyLineDrawingMode();
        addRedTreeBtn.classList.add('active');
        addObjectiveBtn.classList.remove('active');
        addBossBtn.classList.remove('active');
        addBlueTowerBtn.classList.remove('active');
        addRedTowerBtn.classList.remove('active');
        addBlueTreeBtn.classList.remove('active');
        addBlueGooseBtn.classList.remove('active');
        addRedGooseBtn.classList.remove('active');
        drawBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode', 'drawing-mode');
        mapArea.classList.add('placing-mode');
        drawingCanvas.classList.remove('active');
    }
}

// Handle map click for placing objectives/bosses
function handleMapClick(e) {
    if (!placingMode) return;

    // Don't place if clicking on existing markers or buttons
    if (e.target !== mapArea && !e.target.classList.contains('map-placeholder')) {
        return;
    }

    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    if (placingMode === 'objective') {
        placeObjectiveMarker(x, y);
    } else if (placingMode === 'boss') {
        placeBossMarker(x, y);
    } else if (placingMode === 'blue-tower') {
        placeBlueTowerMarker(x, y);
    } else if (placingMode === 'red-tower') {
        placeRedTowerMarker(x, y);
    } else if (placingMode === 'blue-tree') {
        placeBlueTreeMarker(x, y);
    } else if (placingMode === 'red-tree') {
        placeRedTreeMarker(x, y);
    } else if (placingMode === 'blue-goose') {
        placeBlueGooseMarker(x, y);
    } else if (placingMode === 'red-goose') {
        placeRedGooseMarker(x, y);
    }
}

// ============================================================================
// MAP PLACEMENT - OBJECTIVE MARKERS (RED DOTS, BOSSES, TOWERS, TREES)
// ============================================================================

// Place objective marker
function placeObjectiveMarker(x, y, existingId = null) {
    console.log('Placing objective marker at:', x, y);
    const objectiveId = existingId || `objective-${Date.now()}`;

    const marker = document.createElement('div');
    marker.className = 'objective-marker';
    marker.dataset.objectiveId = objectiveId;
    marker.style.left = `${x - 12}px`; // Center the 24px marker
    marker.style.top = `${y - 12}px`;
    marker.draggable = true;

    marker.innerHTML = `
        <button class="remove-btn" onclick="removeObjectiveMarker('${objectiveId}')">×</button>
    `;

    marker.addEventListener('dragstart', handleObjectiveDragStart);
    marker.addEventListener('dragend', handleObjectiveDragEnd);

    mapArea.appendChild(marker);

    placedObjectives.push({
        id: objectiveId,
        x: x,
        y: y
    });

    if (window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'objective', action: 'place', id: objectiveId, x, y });
    }

    savePositions();
    updatePlaceholder();
}

// Place boss marker
function placeBossMarker(x, y, existingId = null) {
    const bossId = existingId || `boss-${Date.now()}`;

    const marker = document.createElement('div');
    marker.className = 'boss-marker';
    marker.dataset.bossId = bossId;
    marker.style.left = `${x - 28}px`; // Center the 56px image
    marker.style.top = `${y - 28}px`;
    marker.draggable = true;

    marker.innerHTML = `
        <img src="/assets/guild_war/images/boss.png" alt="Boss" draggable="false">
        <button class="remove-btn" onclick="removeBossMarker('${bossId}')">×</button>
    `;

    marker.addEventListener('dragstart', handleBossDragStart);
    marker.addEventListener('dragend', handleBossDragEnd);

    mapArea.appendChild(marker);

    placedBosses.push({
        id: bossId,
        x: x,
        y: y
    });

    if (window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'boss', action: 'place', id: bossId, x, y });
    }

    savePositions();
    updatePlaceholder();
}

// Handle objective drag
function handleObjectiveDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.objectiveId);
    e.dataTransfer.setData('type', 'objective-marker');
    e.currentTarget.style.opacity = '0.5';
}

function handleObjectiveDragEnd(e) {
    e.currentTarget.style.opacity = '1';

    const objectiveId = e.currentTarget.dataset.objectiveId;
    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const objective = placedObjectives.find(o => o.id === objectiveId);
    if (objective) {
        objective.x = x;
        objective.y = y;
        e.currentTarget.style.left = `${x - 12}px`; // Center the 24px marker
        e.currentTarget.style.top = `${y - 12}px`;
        savePositions();
    }
}

// Handle boss drag
function handleBossDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.bossId);
    e.dataTransfer.setData('type', 'boss-marker');
    e.currentTarget.style.opacity = '0.5';
}

function handleBossDragEnd(e) {
    e.currentTarget.style.opacity = '1';

    const bossId = e.currentTarget.dataset.bossId;
    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const boss = placedBosses.find(b => b.id === bossId);
    if (boss) {
        boss.x = x;
        boss.y = y;
        e.currentTarget.style.left = `${x - 28}px`; // Center the 56px image
        e.currentTarget.style.top = `${y - 28}px`;
        savePositions();
    }
}

// Remove objective marker
function removeObjectiveMarker(objectiveId) {
    const obj = placedObjectives.find(o => o.id === objectiveId);
    if (obj && window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'objective', action: 'remove', id: objectiveId, x: obj.x, y: obj.y });
    }

    const marker = mapArea.querySelector(`[data-objective-id="${objectiveId}"]`);
    if (marker) {
        marker.remove();
    }
    placedObjectives = placedObjectives.filter(o => o.id !== objectiveId);
    savePositions();
    updatePlaceholder();
}

// Remove boss marker
function removeBossMarker(bossId) {
    const boss = placedBosses.find(b => b.id === bossId);
    if (boss && window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'boss', action: 'remove', id: bossId, x: boss.x, y: boss.y });
    }

    const marker = mapArea.querySelector(`[data-boss-id="${bossId}"]`);
    if (marker) {
        marker.remove();
    }
    placedBosses = placedBosses.filter(b => b.id !== bossId);
    savePositions();
    updatePlaceholder();
}

// Place blue tower marker
function placeBlueTowerMarker(x, y, existingId = null) {
    const towerId = existingId || `blue-tower-${Date.now()}`;

    const marker = document.createElement('div');
    marker.className = 'tower-marker blue-tower';
    marker.dataset.towerId = towerId;
    marker.dataset.towerType = 'blue';
    marker.style.left = `${x - 28}px`; // Center the 56px image
    marker.style.top = `${y - 28}px`;
    marker.draggable = true;

    marker.innerHTML = `
        <img src="/assets/guild_war/images/tower_blue.png" alt="Blue Tower" draggable="false">
        <button class="remove-btn" onclick="removeBlueTowerMarker('${towerId}')">×</button>
    `;

    marker.addEventListener('dragstart', handleBlueTowerDragStart);
    marker.addEventListener('dragend', handleBlueTowerDragEnd);

    mapArea.appendChild(marker);

    placedBlueTowers.push({
        id: towerId,
        x: x,
        y: y
    });

    if (window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'blue-tower', action: 'place', id: towerId, x, y });
    }

    savePositions();
    updatePlaceholder();
}

// Handle blue tower drag
function handleBlueTowerDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.towerId);
    e.dataTransfer.setData('type', 'blue-tower-marker');
    e.currentTarget.style.opacity = '0.5';
}

function handleBlueTowerDragEnd(e) {
    e.currentTarget.style.opacity = '1';

    const towerId = e.currentTarget.dataset.towerId;
    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const tower = placedBlueTowers.find(t => t.id === towerId);
    if (tower) {
        tower.x = x;
        tower.y = y;
        e.currentTarget.style.left = `${x - 28}px`; // Center the 56px image
        e.currentTarget.style.top = `${y - 28}px`;
        savePositions();
    }
}

// Remove blue tower marker
function removeBlueTowerMarker(towerId) {
    const tower = placedBlueTowers.find(t => t.id === towerId);
    if (tower && window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'blue-tower', action: 'remove', id: towerId, x: tower.x, y: tower.y });
    }

    const marker = mapArea.querySelector(`[data-tower-id="${towerId}"]`);
    if (marker) {
        marker.remove();
    }
    placedBlueTowers = placedBlueTowers.filter(t => t.id !== towerId);
    savePositions();
    updatePlaceholder();
}

// Place red tower marker
function placeRedTowerMarker(x, y, existingId = null) {
    const towerId = existingId || `red-tower-${Date.now()}`;

    const marker = document.createElement('div');
    marker.className = 'tower-marker red-tower';
    marker.dataset.towerId = towerId;
    marker.dataset.towerType = 'red';
    marker.style.left = `${x - 28}px`; // Center the 56px image
    marker.style.top = `${y - 28}px`;
    marker.draggable = true;

    marker.innerHTML = `
        <img src="/assets/guild_war/images/tower_red.png" alt="Red Tower" draggable="false">
        <button class="remove-btn" onclick="removeRedTowerMarker('${towerId}')">×</button>
    `;

    marker.addEventListener('dragstart', handleRedTowerDragStart);
    marker.addEventListener('dragend', handleRedTowerDragEnd);

    mapArea.appendChild(marker);

    placedRedTowers.push({
        id: towerId,
        x: x,
        y: y
    });

    if (window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'red-tower', action: 'place', id: towerId, x, y });
    }

    savePositions();
    updatePlaceholder();
}

// Handle red tower drag
function handleRedTowerDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.towerId);
    e.dataTransfer.setData('type', 'red-tower-marker');
    e.currentTarget.style.opacity = '0.5';
}

function handleRedTowerDragEnd(e) {
    e.currentTarget.style.opacity = '1';

    const towerId = e.currentTarget.dataset.towerId;
    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const tower = placedRedTowers.find(t => t.id === towerId);
    if (tower) {
        tower.x = x;
        tower.y = y;
        e.currentTarget.style.left = `${x - 28}px`; // Center the 56px image
        e.currentTarget.style.top = `${y - 28}px`;
        savePositions();
    }
}

// Remove red tower marker
function removeRedTowerMarker(towerId) {
    const tower = placedRedTowers.find(t => t.id === towerId);
    if (tower && window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'red-tower', action: 'remove', id: towerId, x: tower.x, y: tower.y });
    }

    const marker = mapArea.querySelector(`[data-tower-id="${towerId}"]`);
    if (marker) {
        marker.remove();
    }
    placedRedTowers = placedRedTowers.filter(t => t.id !== towerId);
    savePositions();
    updatePlaceholder();
}

// Place tree marker
// Place blue tree marker
function placeBlueTreeMarker(x, y, existingId = null) {
    const treeId = existingId || `blue-tree-${Date.now()}`;

    const marker = document.createElement('div');
    marker.className = 'tree-marker blue-tree';
    marker.dataset.treeId = treeId;
    marker.dataset.treeType = 'blue';
    marker.style.left = `${x - 28}px`; // Center the 56px image
    marker.style.top = `${y - 28}px`;
    marker.draggable = true;

    marker.innerHTML = `
        <img src="/assets/guild_war/images/tree_blue.png" alt="Blue Tree" draggable="false">
        <button class="remove-btn" onclick="removeBlueTreeMarker('${treeId}')">×</button>
    `;

    marker.addEventListener('dragstart', handleBlueTreeDragStart);
    marker.addEventListener('dragend', handleBlueTreeDragEnd);

    mapArea.appendChild(marker);

    placedBlueTrees.push({
        id: treeId,
        x: x,
        y: y
    });

    if (window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'blue-tree', action: 'place', id: treeId, x, y });
    }

    savePositions();
    updatePlaceholder();
}

// Handle blue tree drag
function handleBlueTreeDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.treeId);
    e.dataTransfer.setData('type', 'blue-tree-marker');
    e.currentTarget.style.opacity = '0.5';
}

function handleBlueTreeDragEnd(e) {
    e.currentTarget.style.opacity = '1';

    const treeId = e.currentTarget.dataset.treeId;
    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const tree = placedBlueTrees.find(t => t.id === treeId);
    if (tree) {
        tree.x = x;
        tree.y = y;
        e.currentTarget.style.left = `${x - 28}px`; // Center the 56px image
        e.currentTarget.style.top = `${y - 28}px`;
        savePositions();
    }
}

// Remove blue tree marker
function removeBlueTreeMarker(treeId) {
    const tree = placedBlueTrees.find(t => t.id === treeId);
    if (tree && window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'blue-tree', action: 'remove', id: treeId, x: tree.x, y: tree.y });
    }

    const marker = mapArea.querySelector(`[data-tree-id="${treeId}"]`);
    if (marker) {
        marker.remove();
    }
    placedBlueTrees = placedBlueTrees.filter(t => t.id !== treeId);
    savePositions();
    updatePlaceholder();
}

// Place red tree marker
function placeRedTreeMarker(x, y, existingId = null) {
    const treeId = existingId || `red-tree-${Date.now()}`;

    const marker = document.createElement('div');
    marker.className = 'tree-marker red-tree';
    marker.dataset.treeId = treeId;
    marker.dataset.treeType = 'red';
    marker.style.left = `${x - 28}px`; // Center the 56px image
    marker.style.top = `${y - 28}px`;
    marker.draggable = true;

    marker.innerHTML = `
        <img src="/assets/guild_war/images/tree_red.png" alt="Red Tree" draggable="false">
        <button class="remove-btn" onclick="removeRedTreeMarker('${treeId}')">×</button>
    `;

    marker.addEventListener('dragstart', handleRedTreeDragStart);
    marker.addEventListener('dragend', handleRedTreeDragEnd);

    mapArea.appendChild(marker);

    placedRedTrees.push({
        id: treeId,
        x: x,
        y: y
    });

    if (window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'red-tree', action: 'place', id: treeId, x, y });
    }

    savePositions();
    updatePlaceholder();
}

// Handle red tree drag
function handleRedTreeDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.treeId);
    e.dataTransfer.setData('type', 'red-tree-marker');
    e.currentTarget.style.opacity = '0.5';
}

function handleRedTreeDragEnd(e) {
    e.currentTarget.style.opacity = '1';

    const treeId = e.currentTarget.dataset.treeId;
    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const tree = placedRedTrees.find(t => t.id === treeId);
    if (tree) {
        tree.x = x;
        tree.y = y;
        e.currentTarget.style.left = `${x - 28}px`; // Center the 56px image
        e.currentTarget.style.top = `${y - 28}px`;
        savePositions();
    }
}

// Remove red tree marker
function removeRedTreeMarker(treeId) {
    const tree = placedRedTrees.find(t => t.id === treeId);
    if (tree && window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'red-tree', action: 'remove', id: treeId, x: tree.x, y: tree.y });
    }

    const marker = mapArea.querySelector(`[data-tree-id="${treeId}"]`);
    if (marker) {
        marker.remove();
    }
    placedRedTrees = placedRedTrees.filter(t => t.id !== treeId);
    savePositions();
    updatePlaceholder();
}

// ============================================================================

// ============================================================================
// GOOSE SYSTEM
// ============================================================================

// Toggle blue goose placing mode
function toggleBlueGooseMode() {
    if (placingMode === 'blue-goose') {
        placingMode = null;
        addBlueGooseBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode');
    } else {
        placingMode = 'blue-goose';
        drawingMode = false;
        if (typeof window.polyLineDrawingMode !== 'undefined' && window.polyLineDrawingMode) window.togglePolyLineDrawingMode();
        addBlueGooseBtn.classList.add('active');
        addObjectiveBtn.classList.remove('active');
        addBossBtn.classList.remove('active');
        addBlueTowerBtn.classList.remove('active');
        addRedTowerBtn.classList.remove('active');
        addBlueTreeBtn.classList.remove('active');
        addRedTreeBtn.classList.remove('active');
        addRedGooseBtn.classList.remove('active');
        drawBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode', 'drawing-mode');
        mapArea.classList.add('placing-mode');
        drawingCanvas.classList.remove('active');
    }
}

// Toggle red goose placing mode
function toggleRedGooseMode() {
    if (placingMode === 'red-goose') {
        placingMode = null;
        addRedGooseBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode');
    } else {
        placingMode = 'red-goose';
        drawingMode = false;
        if (typeof window.polyLineDrawingMode !== 'undefined' && window.polyLineDrawingMode) window.togglePolyLineDrawingMode();
        addRedGooseBtn.classList.add('active');
        addObjectiveBtn.classList.remove('active');
        addBossBtn.classList.remove('active');
        addBlueTowerBtn.classList.remove('active');
        addRedTowerBtn.classList.remove('active');
        addBlueTreeBtn.classList.remove('active');
        addRedTreeBtn.classList.remove('active');
        addBlueGooseBtn.classList.remove('active');
        drawBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode', 'drawing-mode');
        mapArea.classList.add('placing-mode');
        drawingCanvas.classList.remove('active');
    }
}

// Place blue goose marker
function placeBlueGooseMarker(x, y, existingId = null) {
    const gooseId = existingId || `blue-goose-${Date.now()}`;

    const marker = document.createElement('div');
    marker.className = 'goose-marker blue-goose';
    marker.dataset.gooseId = gooseId;
    marker.dataset.gooseType = 'blue';
    marker.style.left = `${x - 28}px`;
    marker.style.top = `${y - 28}px`;
    marker.draggable = true;

    marker.innerHTML = `
        <img src="/assets/guild_war/images/goose_blue.png" alt="Blue Goose" draggable="false">
        <button class="remove-btn" onclick="removeBlueGooseMarker('${gooseId}')">×</button>
    `;

    marker.addEventListener('dragstart', handleBlueGooseDragStart);
    marker.addEventListener('dragend', handleBlueGooseDragEnd);

    mapArea.appendChild(marker);

    placedBlueGeese.push({
        id: gooseId,
        x: x,
        y: y
    });

    if (window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'blue-goose', action: 'place', id: gooseId, x, y });
    }

    savePositions();
    updatePlaceholder();
}

function handleBlueGooseDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.gooseId);
    e.dataTransfer.setData('type', 'blue-goose-marker');
    e.currentTarget.style.opacity = '0.5';
}

function handleBlueGooseDragEnd(e) {
    e.currentTarget.style.opacity = '1';

    const gooseId = e.currentTarget.dataset.gooseId;
    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const goose = placedBlueGeese.find(g => g.id === gooseId);
    if (goose) {
        goose.x = x;
        goose.y = y;
        e.currentTarget.style.left = `${x - 28}px`;
        e.currentTarget.style.top = `${y - 28}px`;
        savePositions();
    }
}

function removeBlueGooseMarker(gooseId) {
    const goose = placedBlueGeese.find(g => g.id === gooseId);
    if (goose && window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'blue-goose', action: 'remove', id: gooseId, x: goose.x, y: goose.y });
    }

    const marker = mapArea.querySelector(`[data-goose-id="${gooseId}"]`);
    if (marker) {
        marker.remove();
    }
    placedBlueGeese = placedBlueGeese.filter(g => g.id !== gooseId);
    savePositions();
    updatePlaceholder();
}

// Place red goose marker
function placeRedGooseMarker(x, y, existingId = null) {
    const gooseId = existingId || `red-goose-${Date.now()}`;

    const marker = document.createElement('div');
    marker.className = 'goose-marker red-goose';
    marker.dataset.gooseId = gooseId;
    marker.dataset.gooseType = 'red';
    marker.style.left = `${x - 28}px`;
    marker.style.top = `${y - 28}px`;
    marker.draggable = true;

    marker.innerHTML = `
        <img src="/assets/guild_war/images/goose_red.png" alt="Red Goose" draggable="false">
        <button class="remove-btn" onclick="removeRedGooseMarker('${gooseId}')">×</button>
    `;

    marker.addEventListener('dragstart', handleRedGooseDragStart);
    marker.addEventListener('dragend', handleRedGooseDragEnd);

    mapArea.appendChild(marker);

    placedRedGeese.push({
        id: gooseId,
        x: x,
        y: y
    });

    if (window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'red-goose', action: 'place', id: gooseId, x, y });
    }

    savePositions();
    updatePlaceholder();
}

function handleRedGooseDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.gooseId);
    e.dataTransfer.setData('type', 'red-goose-marker');
    e.currentTarget.style.opacity = '0.5';
}

function handleRedGooseDragEnd(e) {
    e.currentTarget.style.opacity = '1';

    const gooseId = e.currentTarget.dataset.gooseId;
    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const goose = placedRedGeese.find(g => g.id === gooseId);
    if (goose) {
        goose.x = x;
        goose.y = y;
        e.currentTarget.style.left = `${x - 28}px`;
        e.currentTarget.style.top = `${y - 28}px`;
        savePositions();
    }
}

function removeRedGooseMarker(gooseId) {
    const goose = placedRedGeese.find(g => g.id === gooseId);
    if (goose && window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'red-goose', action: 'remove', id: gooseId, x: goose.x, y: goose.y });
    }

    const marker = mapArea.querySelector(`[data-goose-id="${gooseId}"]`);
    if (marker) {
        marker.remove();
    }
    placedRedGeese = placedRedGeese.filter(g => g.id !== gooseId);
    savePositions();
    updatePlaceholder();
}

// ENEMY SYSTEM
// ============================================================================

// Add enemies to the map
function addEnemies() {
    const currentEnemyCount = placedEnemies.length;

    if (currentEnemyCount >= MAX_ENEMIES) {
        alert(GW_CONSTANTS.ALERTS.MAX_ENEMIES_REACHED.replace('{count}', MAX_ENEMIES / ENEMIES_PER_CLICK));
        return;
    }

    const mapRect = mapArea.getBoundingClientRect();

    // Place a group in the center
    const centerX = mapRect.width / 2;
    const centerY = mapRect.height / 2;

    placeEnemyGroup(centerX, centerY);

    updateEnemyCount();
}

// Place enemy group marker on map
function placeEnemyGroup(x, y, existingId = null) {
    const enemyGroupId = existingId || `enemy-group-${Date.now()}`;

    const marker = document.createElement('div');
    marker.className = 'group-marker enemy-group';
    marker.dataset.enemyGroupId = enemyGroupId;
    marker.style.left = `${x}px`;
    marker.style.top = `${y}px`;
    marker.draggable = true;

    marker.innerHTML = `
        <div class="group-number">5</div>
        <div class="group-tooltip">
            <div class="tooltip-header">${GW_CONSTANTS.TOOLTIPS.ENEMY_GROUP}</div>
            <div class="tooltip-info">5 ${GW_CONSTANTS.TOOLTIPS.ENEMY_PLAYERS}</div>
        </div>
        <button class="remove-btn" onclick="removeEnemyGroup('${enemyGroupId}')">×</button>
    `;

    marker.addEventListener('dragstart', handleEnemyGroupDragStart);
    marker.addEventListener('dragend', handleEnemyGroupDragEnd);

    mapArea.appendChild(marker);

    placedEnemies.push({
        id: enemyGroupId,
        x: x,
        y: y,
        count: ENEMIES_PER_CLICK
    });

    if (window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'enemy-group', action: 'place', id: enemyGroupId, x, y });
    }

    savePositions();
    updatePlaceholder();
}

// Handle enemy group drag
function handleEnemyGroupDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.enemyGroupId);
    e.dataTransfer.setData('type', 'enemy-group-marker');
    e.currentTarget.style.opacity = '0.5';
}

function handleEnemyGroupDragEnd(e) {
    e.currentTarget.style.opacity = '1';

    const enemyGroupId = e.currentTarget.dataset.enemyGroupId;
    const rect = mapArea.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const enemyGroup = placedEnemies.find(eg => eg.id === enemyGroupId);
    if (enemyGroup) {
        enemyGroup.x = x;
        enemyGroup.y = y;
        e.currentTarget.style.left = `${x}px`;
        e.currentTarget.style.top = `${y}px`;
        savePositions();
    }
}

// Remove enemy group
function removeEnemyGroup(enemyGroupId) {
    const enemy = placedEnemies.find(e => e.id === enemyGroupId);
    if (enemy && window.recordMarkerAction && !window.isUndoRedo) {
        window.recordMarkerAction({ kind: 'enemy-group', action: 'remove', id: enemyGroupId, x: enemy.x, y: enemy.y });
    }

    const marker = mapArea.querySelector(`[data-enemy-group-id="${enemyGroupId}"]`);
    if (marker) {
        marker.remove();
    }
    placedEnemies = placedEnemies.filter(e => e.id !== enemyGroupId);
    savePositions();
    updatePlaceholder();
    updateEnemyCount();
}

// Update enemy count display
function updateEnemyCount() {
    const totalEnemies = placedEnemies.length * ENEMIES_PER_CLICK;
    enemyCount.textContent = totalEnemies;

    // Disable button if max reached
    if (placedEnemies.length >= MAX_ENEMIES / ENEMIES_PER_CLICK) {
        addEnemiesBtn.disabled = true;
    } else {
        addEnemiesBtn.disabled = false;
    }
}

// Initialize drawing canvas
function initializeCanvas() {
    resizeCanvas();

    let isDrawing = false;
    let currentPath = [];

    drawingCanvas.addEventListener('mousedown', (e) => {
        if (!drawingMode) return;
        e.preventDefault();

        isDrawing = true;
        const rect = drawingCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        currentPath = [{ x, y }];

        // Start drawing immediately
        ctx.strokeStyle = drawingColor;
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(x, y);
    });

    drawingCanvas.addEventListener('mousemove', (e) => {
        if (!drawingMode || !isDrawing) return;
        e.preventDefault();

        const rect = drawingCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        currentPath.push({ x, y });

        // Draw line segment
        ctx.lineTo(x, y);
        ctx.stroke();
    });

    drawingCanvas.addEventListener('mouseup', (e) => {
        if (!drawingMode || !isDrawing) return;
        e.preventDefault();

        isDrawing = false;

        if (currentPath.length > 1) {
            const pathData = {
                points: [...currentPath],
                timestamp: Date.now(),
                color: drawingColor,
                width: 3
            };

            drawingPaths.push(pathData);

            // Only add to history if auto-delete is OFF
            if (!autoDeleteDrawings) {
                // Create a deep copy of the current state
                const stateCopy = drawingPaths.map(path => ({
                    points: [...path.points],
                    timestamp: path.timestamp,
                    color: path.color,
                    width: path.width
                }));
                drawingHistory.push(stateCopy);
                drawingRedoStack = []; // Clear redo stack when new action is made
                updateUndoRedoButtons();
            }

            // Set up auto-delete if enabled
            if (autoDeleteDrawings) {
                schedulePathDeletion(drawingPaths.length - 1);
            }
        }

        currentPath = [];
    });

    drawingCanvas.addEventListener('mouseleave', () => {
        if (isDrawing) {
            isDrawing = false;
            currentPath = [];
        }
    });

    // Touch support for mobile/tablets
    drawingCanvas.addEventListener('touchstart', (e) => {
        if (!drawingMode) return;
        e.preventDefault();

        const touch = e.touches[0];
        const rect = drawingCanvas.getBoundingClientRect();
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;

        isDrawing = true;
        currentPath = [{ x, y }];

        ctx.strokeStyle = drawingColor;
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.beginPath();
        ctx.moveTo(x, y);
    });

    drawingCanvas.addEventListener('touchmove', (e) => {
        if (!drawingMode || !isDrawing) return;
        e.preventDefault();

        const touch = e.touches[0];
        const rect = drawingCanvas.getBoundingClientRect();
        const x = touch.clientX - rect.left;
        const y = touch.clientY - rect.top;

        currentPath.push({ x, y });
        ctx.lineTo(x, y);
        ctx.stroke();
    });

    drawingCanvas.addEventListener('touchend', (e) => {
        if (!drawingMode || !isDrawing) return;
        e.preventDefault();

        isDrawing = false;

        if (currentPath.length > 1) {
            const pathData = {
                points: [...currentPath],
                timestamp: Date.now(),
                color: drawingColor,
                width: 3
            };

            drawingPaths.push(pathData);

            // Only add to history if auto-delete is OFF
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

        currentPath = [];
    });
}

// Resize canvas to match map area
function resizeCanvas() {
    const rect = mapArea.getBoundingClientRect();
    drawingCanvas.width = rect.width;
    drawingCanvas.height = rect.height;
    redrawAllPaths();
}

// ============================================================================
// DRAWING SYSTEM
// ============================================================================

// Draw arrow head
function drawArrow(ctx, fromx, fromy, tox, toy, color, width) {
    const headlen = 15; // length of head in pixels
    const dx = tox - fromx;
    const dy = toy - fromy;
    const angle = Math.atan2(dy, dx);

    ctx.beginPath();
    ctx.moveTo(tox, toy);
    ctx.lineTo(tox - headlen * Math.cos(angle - Math.PI / 6), toy - headlen * Math.sin(angle - Math.PI / 6));
    ctx.moveTo(tox, toy);
    ctx.lineTo(tox - headlen * Math.cos(angle + Math.PI / 6), toy - headlen * Math.sin(angle + Math.PI / 6));

    ctx.strokeStyle = color;
    ctx.lineWidth = width;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.stroke();
}

// Draw a single path
function drawPath(points, color, width) {
    if (points.length < 2) return;

    ctx.strokeStyle = color;
    ctx.lineWidth = width;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    ctx.beginPath();
    ctx.moveTo(points[0].x, points[0].y);

    for (let i = 1; i < points.length; i++) {
        ctx.lineTo(points[i].x, points[i].y);
    }

    ctx.stroke();

    // Draw arrow at the end
    if (points.length >= 2) {
        // Find a point that is far enough from the end to calculate a stable angle
        // Simple approach: use the point 5 steps back or the start point
        let prevIndex = Math.max(0, points.length - 5);
        const lastPoint = points[points.length - 1];
        const prevPoint = points[prevIndex];

        // If the path is very short, just use the previous point
        if (prevIndex === points.length - 1) {
             prevIndex = points.length - 2;
        }

        const effectivePrevPoint = points[prevIndex];

        drawArrow(ctx, effectivePrevPoint.x, effectivePrevPoint.y, lastPoint.x, lastPoint.y, color, width);
    }
}

// Redraw all paths
function redrawAllPaths() {
    ctx.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);

    drawingPaths.forEach(pathData => {
        drawPath(pathData.points, pathData.color, pathData.width);
    });
}

// Toggle drawing mode
function toggleDrawingMode() {
    if (drawingMode) {
        // Deactivate
        drawingMode = false;
        drawBtn.classList.remove('active');
        mapArea.classList.remove('drawing-mode');
        drawingCanvas.classList.remove('active');
    } else {
        // Activate drawing mode
        drawingMode = true;
        placingMode = null;
        drawBtn.classList.add('active');
        addObjectiveBtn.classList.remove('active');
        addBossBtn.classList.remove('active');
        addBlueTowerBtn.classList.remove('active');
        addRedTowerBtn.classList.remove('active');
        addBlueTreeBtn.classList.remove('active');
        addRedTreeBtn.classList.remove('active');
    addBlueGooseBtn.classList.remove('active');
    addRedGooseBtn.classList.remove('active');
        mapArea.classList.remove('placing-mode');
        mapArea.classList.add('drawing-mode');
        drawingCanvas.classList.add('active');
    }
}

// Clear all drawings
async function clearAllDrawings() {
    if (drawingPaths.length === 0) return;

    const confirmed = await showConfirm(
        'Clear All Drawings',
        'Are you sure you want to clear all drawings?'
    );

    if (confirmed) {
        // Save current state to history before clearing (deep copy)
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
        redrawAllPaths();
        updateUndoRedoButtons();
    }
}

// Undo drawing
window.undoDrawing = function() {
    if (autoDeleteDrawings) {
        alert('Undo is not available when auto-delete is enabled. Please disable auto-delete first.');
        return;
    }

    if (drawingHistory.length === 0) return;

    // Save current state to redo stack (deep copy)
    const currentStateCopy = drawingPaths.map(path => ({
        points: [...path.points],
        timestamp: path.timestamp,
        color: path.color,
        width: path.width
    }));
    drawingRedoStack.push(currentStateCopy);

    // Restore previous state (pop returns the last element)
    drawingHistory.pop(); // Remove current state
    const previousState = drawingHistory[drawingHistory.length - 1]; // Get previous state

    if (previousState) {
        // Deep copy the previous state
        drawingPaths = previousState.map(path => ({
            points: [...path.points],
            timestamp: path.timestamp,
            color: path.color,
            width: path.width
        }));
    } else {
        // No previous state means go back to empty
        drawingPaths = [];
    }

    redrawAllPaths();
    updateUndoRedoButtons();
}

// Redo drawing
window.redoDrawing = function() {
    if (autoDeleteDrawings) {
        alert('Redo is not available when auto-delete is enabled. Please disable auto-delete first.');
        return;
    }

    if (drawingRedoStack.length === 0) return;

    // Get the next state from redo stack
    const nextState = drawingRedoStack.pop();

    if (nextState) {
        // Save current state to history (deep copy)
        const currentStateCopy = drawingPaths.map(path => ({
            points: [...path.points],
            timestamp: path.timestamp,
            color: path.color,
            width: path.width
        }));
        drawingHistory.push(currentStateCopy);

        // Restore next state (deep copy)
        drawingPaths = nextState.map(path => ({
            points: [...path.points],
            timestamp: path.timestamp,
            color: path.color,
            width: path.width
        }));
    }

    redrawAllPaths();
    updateUndoRedoButtons();
}

// Update undo/redo button states
function updateUndoRedoButtons() {
    if (autoDeleteDrawings) {
        undoDrawBtn.disabled = true;
        redoDrawBtn.disabled = true;
    } else {
        undoDrawBtn.disabled = drawingHistory.length === 0;
        redoDrawBtn.disabled = drawingRedoStack.length === 0;
    }
}

// Handle auto-delete toggle
function handleAutoDeleteToggle(e) {
    autoDeleteDrawings = e.target.checked;

    if (autoDeleteDrawings) {
        // Clear history when enabling auto-delete
        drawingHistory = [];
        drawingRedoStack = [];
        updateUndoRedoButtons();

        // Schedule deletion for existing paths
        drawingPaths.forEach((path, index) => {
            const elapsed = Date.now() - path.timestamp;
            const remaining = AUTO_DELETE_DELAY - elapsed;

            if (remaining > 0) {
                schedulePathDeletion(index, remaining);
            } else {
                // Already expired, delete immediately
                drawingPaths[index] = null;
            }
        });

        // Clean up null entries
        drawingPaths = drawingPaths.filter(p => p !== null);
        redrawAllPaths();
    } else {
        // Clear all timers when disabling auto-delete
        drawingDeleteTimers.forEach(timer => clearTimeout(timer));
        drawingDeleteTimers = [];

        // Initialize history with current state (deep copy)
        if (drawingPaths.length > 0) {
            const stateCopy = drawingPaths.map(path => ({
                points: [...path.points],
                timestamp: path.timestamp,
                color: path.color,
                width: path.width
            }));
            drawingHistory = [stateCopy];
        } else {
            drawingHistory = [[]]; // Empty state
        }
        updateUndoRedoButtons();
    }
}

// Schedule path deletion
function schedulePathDeletion(index, delay = AUTO_DELETE_DELAY) {
    const timer = setTimeout(() => {
        if (drawingPaths[index]) {
            drawingPaths.splice(index, 1);
            redrawAllPaths();

            // Remove this timer from the list
            const timerIndex = drawingDeleteTimers.indexOf(timer);
            if (timerIndex > -1) {
                drawingDeleteTimers.splice(timerIndex, 1);
            }
        }
    }, delay);

    drawingDeleteTimers.push(timer);
}

// ============================================================================
// SPLIT MEMBER FEATURE
// ============================================================================

// Toggle split member view
function toggleSplitView(groupId) {
    const splitDiv = document.getElementById(`split-${groupId}`);
    if (splitDiv) {
        const isCurrentlyOpen = splitDiv.style.display !== 'none';

        // Close any other open split views
        if (activeSplitGroupId && activeSplitGroupId !== groupId) {
            const otherSplitDiv = document.getElementById(`split-${activeSplitGroupId}`);
            if (otherSplitDiv) {
                otherSplitDiv.style.display = 'none';
            }
        }

        // Toggle current split view
        if (isCurrentlyOpen) {
            splitDiv.style.display = 'none';
            activeSplitGroupId = null;
        } else {
            splitDiv.style.display = 'block';
            activeSplitGroupId = groupId;
        }
    }
}

// Split member from group - place near the group
function splitMemberFromGroup(groupId, memberId) {
    const group = placedGroups.find(g => g.id === groupId);
    if (!group) return;

    // Get member info
    const member = members.find(m => m.id === memberId);
    if (!member) return;

    // Remove member from group
    group.memberIds = group.memberIds.filter(id => id !== memberId);

    // Calculate position near the group (offset by 50px to the right)
    const offsetX = 50;
    const offsetY = 0;
    const newX = group.x + offsetX;
    const newY = group.y + offsetY;

    // If group is empty, remove it
    if (group.memberIds.length === 0) {
        removeGroupMarker(groupId);
    } else {
        // Update the group marker
        const marker = mapArea.querySelector(`[data-group-id="${groupId}"]`);
        if (marker) {
            updateGroupMarker(marker, group);
        }
    }

    // Place member individually near the group
    placeMemberOnMap(member, newX, newY);

    savePositions();
    updateCounts();
    renderMemberList();
}

function splitGroup(groupId) {
    const group = placedGroups.find(g => g.id === groupId);
    if (!group) return;

    if (group.teams.length <= 1) {
        alert('This group only contains one team. Nothing to split.');
        return;
    }

    // Remove the original group marker
    const marker = mapArea.querySelector(`[data-group-id="${groupId}"]`);
    if (marker) {
        marker.remove();
    }

    // Remove from placedGroups array
    placedGroups = placedGroups.filter(g => g.id !== groupId);

    // Create separate groups for each team
    const baseX = group.x;
    const baseY = group.y;
    const offset = 45; // pixels to offset each new group (reduced from 60)

    group.teams.forEach((teamName, index) => {
        // Get members for this team
        const teamMemberIds = group.memberIds.filter(id => {
            const member = members.find(m => m.id === id);
            return member && member.team === teamName;
        });

        if (teamMemberIds.length > 0) {
            // Calculate position with offset in a circular pattern
            const angle = (index / group.teams.length) * 2 * Math.PI;
            const newX = baseX + Math.cos(angle) * offset;
            const newY = baseY + Math.sin(angle) * offset;

            // Create new group
            const newGroupId = `group-${Date.now()}-${index}`;
            const newGroup = {
                id: newGroupId,
                teams: [teamName],
                memberIds: teamMemberIds,
                x: newX,
                y: newY
            };

            placedGroups.push(newGroup);
            renderGroupMarker(newGroup);
        }
    });

    savePositions();
    updateCounts();
}

// Place member marker on map
function placeMemberOnMap(member, x, y) {
    const marker = document.createElement('div');
    marker.className = `member-marker role-${member.role}`;
    marker.dataset.memberId = member.id;
    marker.style.left = `${x - 8}px`; // Center the 16px marker
    marker.style.top = `${y - 8}px`;
    marker.draggable = true;

    const displayTeamName = getTeamDisplayName(member.team);

    marker.innerHTML = `
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

    // Make marker draggable within map
    marker.addEventListener('dragstart', handleMarkerDragStart);
    marker.addEventListener('dragend', handleMarkerDragEnd);

    mapArea.appendChild(marker);

    placedMembers.push({
        memberId: member.id,
        x: x,
        y: y
    });

    // Check if this member was part of a group and update the group
    updateGroupsAfterMemberPlacement(member.id);

    savePositions();
    updateCounts();
    updatePlaceholder();
    renderMemberList(); // Re-render to hide placed member
}

// Handle marker drag
function handleMarkerDragStart(e) {
    e.dataTransfer.effectAllowed = 'move';
    const dragData = {
        type: 'member-marker',
        memberId: e.currentTarget.dataset.memberId
    };
    e.dataTransfer.setData('text/plain', JSON.stringify(dragData));
    e.currentTarget.style.opacity = '0.5';
}

function handleMarkerDragEnd(e) {
    e.currentTarget.style.opacity = '1';
}

// Remove member marker
function removeMemberMarker(memberId) {
    const marker = mapArea.querySelector(`[data-member-id="${memberId}"]`);
    if (marker) {
        marker.remove();
    }
    placedMembers = placedMembers.filter(p => p.memberId !== memberId);
    savePositions();
    updateCounts();
    updatePlaceholder();
    renderMemberList(); // Re-render to show member again
}

// Search functionality
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    applyFilters(searchTerm);
}

// View toggle functionality
function handleViewToggle(e) {
    viewToggleButtons.forEach(btn => btn.classList.remove('active'));
    e.target.classList.add('active');

    currentView = e.target.dataset.view;
    renderMemberList();
}

// Role filter functionality
function handleRoleFilter(e) {
    roleFilterButtons.forEach(btn => btn.classList.remove('active'));
    e.target.classList.add('active');

    currentRoleFilter = e.target.dataset.role;
    const searchTerm = searchInput.value.toLowerCase();
    applyFilters(searchTerm);
}

// Apply all filters
function applyFilters(searchTerm = '') {
    filteredMembers = members.filter(member => {
        const matchesSearch = member.name.toLowerCase().includes(searchTerm) ||
                            member.role.toLowerCase().includes(searchTerm) ||
                            member.team.toLowerCase().includes(searchTerm);

        const matchesRole = currentRoleFilter === 'all' || member.role === currentRoleFilter;

        return matchesSearch && matchesRole;
    });

    renderMemberList();
}

// Clear all placements
async function clearAllPlacements() {
    const totalPlaced = getTotalPlacedPlayers();
    const totalMarkers = placedObjectives.length + placedBosses.length + placedBlueTowers.length + placedRedTowers.length + placedBlueTrees.length + placedRedTrees.length + placedBlueGeese.length + placedRedGeese.length + placedEnemies.length;
    if (totalPlaced === 0 && totalMarkers === 0) return;

    const confirmed = await showConfirm(
        'Clear All Placements',
        'Are you sure you want to remove all players and markers from the map?'
    );

    if (confirmed) {
        const markers = mapArea.querySelectorAll('.member-marker, .group-marker, .objective-marker, .boss-marker, .tower-marker, .tree-marker, .goose-marker, .enemy-marker');
        markers.forEach(marker => marker.remove());
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
        updateEnemyCount();
        renderMemberList(); // Re-render to show all members again
    }
}

// Update player counts
function updateCounts() {
    playerCount.textContent = `(${members.length}/${MAX_PLAYERS})`;
    const totalPlaced = getTotalPlacedPlayers();
    placedCount.textContent = `(${totalPlaced}/${MAX_PLAYERS} Placed)`;
}

// Update placeholder visibility
function updatePlaceholder() {
    const placeholder = document.querySelector('.map-placeholder');
    if (placeholder) {
        const hasContent = placedMembers.length > 0 || placedGroups.length > 0 ||
                          placedObjectives.length > 0 || placedBosses.length > 0 ||
                          placedBlueTowers.length > 0 || placedRedTowers.length > 0 || placedBlueTrees.length > 0 || placedRedTrees.length > 0 || placedBlueGeese.length > 0 || placedRedGeese.length > 0 || placedEnemies.length > 0;
        placeholder.style.display = hasContent ? 'none' : 'block';
    }
}

// ============================================================================
// DATA PERSISTENCE & EXPORT
// ============================================================================

// Render all map markers from data
function renderMap() {
    // Clear existing markers
    const existingMarkers = mapArea.querySelectorAll('.member-marker, .group-marker, .objective-marker, .boss-marker, .tower-marker, .tree-marker, .enemy-marker');
    existingMarkers.forEach(marker => marker.remove());

    // Render individual member markers
    placedMembers.forEach(placement => {
        const member = members.find(m => m.id === placement.memberId);
        if (!member) return;

        const marker = document.createElement('div');
        marker.className = 'member-marker';
        marker.dataset.memberId = placement.memberId;
        marker.style.left = `${placement.x - 8}px`;
        marker.style.top = `${placement.y - 8}px`;
        marker.draggable = true;

        const displayTeamName = getTeamDisplayName(member.team);

        marker.innerHTML = `
            <div class="marker-tooltip">
                <div class="tooltip-name">${member.name}</div>
                <div class="tooltip-weapons">
                    ${member.weapon1 ? `<div>⚔️ ${member.weapon1}</div>` : ''}
                    ${member.weapon2 ? `<div>🛡️ ${member.weapon2}</div>` : ''}
                </div>
                <div class="tooltip-info">${member.role} | ${displayTeamName}</div>
            </div>
            <button class="remove-btn" onclick="removeMemberMarker(${placement.memberId})">×</button>
        `;

        marker.addEventListener('dragstart', handleMarkerDragStart);
        marker.addEventListener('dragend', handleMarkerDragEnd);

        mapArea.appendChild(marker);
    });

    // Render group markers
    placedGroups.forEach(group => {
        renderGroupMarker(group);
    });

    // Render objectives
    placedObjectives.forEach(obj => {
        const marker = document.createElement('div');
        marker.className = 'objective-marker';
        marker.dataset.objectiveId = obj.id;
        marker.style.left = `${obj.x - 12}px`;
        marker.style.top = `${obj.y - 12}px`;
        marker.draggable = true;
        marker.innerHTML = '<button class="remove-btn" onclick="removeObjectiveMarker(\'' + obj.id + '\')">×</button>';

        marker.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', obj.id);
            e.dataTransfer.setData('type', 'objective-marker');
            e.currentTarget.style.opacity = '0.5';
        });

        marker.addEventListener('dragend', (e) => {
            e.currentTarget.style.opacity = '1';
            const rect = mapArea.getBoundingClientRect();
            const x = e.clientX - rect.left + 12;
            const y = e.clientY - rect.top + 12;
            const objIndex = placedObjectives.findIndex(o => o.id === obj.id);
            if (objIndex !== -1) {
                placedObjectives[objIndex].x = x;
                placedObjectives[objIndex].y = y;
                marker.style.left = `${x - 12}px`;
                marker.style.top = `${y - 12}px`;
                savePositions();
            }
        });

        mapArea.appendChild(marker);
    });

    // Render bosses
    placedBosses.forEach(boss => {
        const marker = document.createElement('div');
        marker.className = 'boss-marker';
        marker.dataset.bossId = boss.id;
        marker.style.left = `${boss.x - 28}px`;
        marker.style.top = `${boss.y - 28}px`;
        marker.draggable = true;
        marker.innerHTML = '<button class="remove-btn" onclick="removeBossMarker(\'' + boss.id + '\')">×</button>';

        marker.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', boss.id);
            e.dataTransfer.setData('type', 'boss-marker');
            e.currentTarget.style.opacity = '0.5';
        });

        marker.addEventListener('dragend', (e) => {
            e.currentTarget.style.opacity = '1';
            const rect = mapArea.getBoundingClientRect();
            const x = e.clientX - rect.left + 28;
            const y = e.clientY - rect.top + 28;
            const bossIndex = placedBosses.findIndex(b => b.id === boss.id);
            if (bossIndex !== -1) {
                placedBosses[bossIndex].x = x;
                placedBosses[bossIndex].y = y;
                marker.style.left = `${x - 28}px`;
                marker.style.top = `${y - 28}px`;
                savePositions();
            }
        });

        mapArea.appendChild(marker);
    });

    // Render blue towers
    placedBlueTowers.forEach(tower => {
        const marker = document.createElement('div');
        marker.className = 'tower-marker blue-tower';
        marker.dataset.towerId = tower.id;
        marker.dataset.towerType = 'blue';
        marker.style.left = `${tower.x - 28}px`;
        marker.style.top = `${tower.y - 28}px`;
        marker.draggable = true;
        marker.innerHTML = `
            <img src="/assets/guild_war/images/tower_blue.png" alt="Blue Tower" draggable="false">
            <button class="remove-btn" onclick="removeBlueTowerMarker('${tower.id}')">×</button>
        `;

        marker.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', tower.id);
            e.dataTransfer.setData('type', 'blue-tower-marker');
            e.currentTarget.style.opacity = '0.5';
        });

        marker.addEventListener('dragend', (e) => {
            e.currentTarget.style.opacity = '1';
            const rect = mapArea.getBoundingClientRect();
            const x = e.clientX - rect.left + 28;
            const y = e.clientY - rect.top + 28;
            const towerIndex = placedBlueTowers.findIndex(t => t.id === tower.id);
            if (towerIndex !== -1) {
                placedBlueTowers[towerIndex].x = x;
                placedBlueTowers[towerIndex].y = y;
                marker.style.left = `${x - 28}px`;
                marker.style.top = `${y - 28}px`;
                savePositions();
            }
        });

        mapArea.appendChild(marker);
    });

    // Render red towers
    placedRedTowers.forEach(tower => {
        const marker = document.createElement('div');
        marker.className = 'tower-marker red-tower';
        marker.dataset.towerId = tower.id;
        marker.dataset.towerType = 'red';
        marker.style.left = `${tower.x - 28}px`;
        marker.style.top = `${tower.y - 28}px`;
        marker.draggable = true;
        marker.innerHTML = `
            <img src="/assets/guild_war/images/tower_red.png" alt="Red Tower" draggable="false">
            <button class="remove-btn" onclick="removeRedTowerMarker('${tower.id}')">×</button>
        `;

        marker.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', tower.id);
            e.dataTransfer.setData('type', 'red-tower-marker');
            e.currentTarget.style.opacity = '0.5';
        });

        marker.addEventListener('dragend', (e) => {
            e.currentTarget.style.opacity = '1';
            const rect = mapArea.getBoundingClientRect();
            const x = e.clientX - rect.left + 28;
            const y = e.clientY - rect.top + 28;
            const towerIndex = placedRedTowers.findIndex(t => t.id === tower.id);
            if (towerIndex !== -1) {
                placedRedTowers[towerIndex].x = x;
                placedRedTowers[towerIndex].y = y;
                marker.style.left = `${x - 28}px`;
                marker.style.top = `${y - 28}px`;
                savePositions();
            }
        });

        mapArea.appendChild(marker);
    });

    // Render trees
    placedTrees.forEach(tree => {
        const marker = document.createElement('div');
        marker.className = 'tree-marker';
        marker.dataset.treeId = tree.id;
        marker.style.left = `${tree.x - 20}px`;
        marker.style.top = `${tree.y - 20}px`;
        marker.draggable = true;
        marker.innerHTML = '<button class="remove-btn" onclick="removeTreeMarker(\'' + tree.id + '\')">×</button>';

        marker.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', tree.id);
            e.dataTransfer.setData('type', 'tree-marker');
            e.currentTarget.style.opacity = '0.5';
        });

        marker.addEventListener('dragend', (e) => {
            e.currentTarget.style.opacity = '1';
            const rect = mapArea.getBoundingClientRect();
            const x = e.clientX - rect.left + 20;
            const y = e.clientY - rect.top + 20;
            const treeIndex = placedTrees.findIndex(t => t.id === tree.id);
            if (treeIndex !== -1) {
                placedTrees[treeIndex].x = x;
                placedTrees[treeIndex].y = y;
                marker.style.left = `${x - 20}px`;
                marker.style.top = `${y - 20}px`;
                savePositions();
            }
        });

        mapArea.appendChild(marker);
    });


    // Render blue geese
    placedBlueGeese.forEach(goose => {
        const marker = document.createElement('div');
        marker.className = 'goose-marker blue-goose';
        marker.dataset.gooseId = goose.id;
        marker.dataset.gooseType = 'blue';
        marker.style.left = `${goose.x - 28}px`;
        marker.style.top = `${goose.y - 28}px`;
        marker.draggable = true;
        marker.innerHTML = `
            <img src="/assets/guild_war/images/goose_blue.png" alt="Blue Goose" draggable="false">
            <button class="remove-btn" onclick="removeBlueGooseMarker('${goose.id}')">×</button>
        `;

        marker.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', goose.id);
            e.dataTransfer.setData('type', 'blue-goose-marker');
            e.currentTarget.style.opacity = '0.5';
        });

        marker.addEventListener('dragend', (e) => {
            e.currentTarget.style.opacity = '1';
            const rect = mapArea.getBoundingClientRect();
            const x = e.clientX - rect.left + 28;
            const y = e.clientY - rect.top + 28;
            const gooseIndex = placedBlueGeese.findIndex(g => g.id === goose.id);
            if (gooseIndex !== -1) {
                placedBlueGeese[gooseIndex].x = x;
                placedBlueGeese[gooseIndex].y = y;
                marker.style.left = `${x - 28}px`;
                marker.style.top = `${y - 28}px`;
                savePositions();
            }
        });

        mapArea.appendChild(marker);
    });

    // Render red geese
    placedRedGeese.forEach(goose => {
        const marker = document.createElement('div');
        marker.className = 'goose-marker red-goose';
        marker.dataset.gooseId = goose.id;
        marker.dataset.gooseType = 'red';
        marker.style.left = `${goose.x - 28}px`;
        marker.style.top = `${goose.y - 28}px`;
        marker.draggable = true;
        marker.innerHTML = `
            <img src="/assets/guild_war/images/goose_red.png" alt="Red Goose" draggable="false">
            <button class="remove-btn" onclick="removeRedGooseMarker('${goose.id}')">×</button>
        `;

        marker.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', goose.id);
            e.dataTransfer.setData('type', 'red-goose-marker');
            e.currentTarget.style.opacity = '0.5';
        });

        marker.addEventListener('dragend', (e) => {
            e.currentTarget.style.opacity = '1';
            const rect = mapArea.getBoundingClientRect();
            const x = e.clientX - rect.left + 28;
            const y = e.clientY - rect.top + 28;
            const gooseIndex = placedRedGeese.findIndex(g => g.id === goose.id);
            if (gooseIndex !== -1) {
                placedRedGeese[gooseIndex].x = x;
                placedRedGeese[gooseIndex].y = y;
                marker.style.left = `${x - 28}px`;
                marker.style.top = `${y - 28}px`;
                savePositions();
            }
        });

        mapArea.appendChild(marker);
    });
    // Render enemies
    placedEnemies.forEach(enemy => {
        const marker = document.createElement('div');
        marker.className = 'enemy-marker';
        marker.dataset.enemyId = enemy.id;
        marker.style.left = `${enemy.x - 16}px`;
        marker.style.top = `${enemy.y - 16}px`;
        marker.draggable = true;
        marker.innerHTML = `
            <div class="group-number">${enemy.count}</div>
            <button class="remove-btn" onclick="removeEnemyMarker('${enemy.id}')">×</button>
        `;

        marker.addEventListener('dragstart', (e) => {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', enemy.id);
            e.dataTransfer.setData('type', 'enemy-marker');
            e.currentTarget.style.opacity = '0.5';
        });

        marker.addEventListener('dragend', (e) => {
            e.currentTarget.style.opacity = '1';
            const rect = mapArea.getBoundingClientRect();
            const x = e.clientX - rect.left + 16;
            const y = e.clientY - rect.top + 16;
            const enemyIndex = placedEnemies.findIndex(en => en.id === enemy.id);
            if (enemyIndex !== -1) {
                placedEnemies[enemyIndex].x = x;
                placedEnemies[enemyIndex].y = y;
                marker.style.left = `${x - 16}px`;
                marker.style.top = `${y - 16}px`;
                savePositions();
            }
        });

        mapArea.appendChild(marker);
    });

    // Update placeholder and counts
    updatePlaceholder();
}

// Export positions
function exportPositions() {
    // Show progress indicator
    showExportProgress();

    // Use setTimeout to allow UI to update
    setTimeout(() => {
        try {
            const exportData = {
                version: '1.0',
                exportDate: new Date().toISOString(),
                // Export the complete player list
                players: members.map(member => ({
                    id: member.id,
                    name: member.name,
                    role: member.role,
                    team: member.team,
                    weapon1: member.weapon1 || '',
                    weapon2: member.weapon2 || ''
                })),
                // Export placed items
                individuals: placedMembers.map(placement => ({
                    memberId: placement.memberId,
                    x: Math.round(placement.x),
                    y: Math.round(placement.y)
                })),
                groups: placedGroups.map(group => ({
                    id: group.id,
                    teams: group.teams,
                    memberIds: group.memberIds,
                    x: Math.round(group.x),
                    y: Math.round(group.y)
                })),
                objectives: placedObjectives.map(obj => ({
                    id: obj.id,
                    x: Math.round(obj.x),
                    y: Math.round(obj.y)
                })),
                bosses: placedBosses.map(boss => ({
                    id: boss.id,
                    x: Math.round(boss.x),
                    y: Math.round(boss.y)
                })),
                blueTowers: placedBlueTowers.map(tower => ({
                    id: tower.id,
                    x: Math.round(tower.x),
                    y: Math.round(tower.y)
                })),
                redTowers: placedRedTowers.map(tower => ({
                    id: tower.id,
                    x: Math.round(tower.x),
                    y: Math.round(tower.y)
                })),
                blueTrees: placedBlueTrees.map(tree => ({
                    id: tree.id,
                    x: Math.round(tree.x),
                    y: Math.round(tree.y)
                })),
                                redTrees: placedRedTrees.map(tree => ({
                    id: tree.id,
                    x: Math.round(tree.x),
                    y: Math.round(tree.y)
                })),
                blueGeese: placedBlueGeese.map(goose => ({
                    id: goose.id,
                    x: Math.round(goose.x),
                    y: Math.round(goose.y)
                })),
                redGeese: placedRedGeese.map(goose => ({
                    id: goose.id,
                    x: Math.round(goose.x),
                    y: Math.round(goose.y)
                })),
                enemies: placedEnemies.map(enemy => ({
                    id: enemy.id,
                    x: Math.round(enemy.x),
                    y: Math.round(enemy.y),
                    count: enemy.count
                })),
                // Export drawings
                drawings: drawingPaths.map(path => ({
                    id: path.id,
                    points: path.points,
                    color: path.color || '#ff0000',
                    width: path.width || 3
                })),
                // Export team name mappings (custom renamed teams)
                teamNames: teamNameMappings
            };

            const dataStr = JSON.stringify(exportData, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `guild-war-strategy-${Date.now()}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Clean up
            setTimeout(() => {
                URL.revokeObjectURL(url);
                hideExportProgress();
            }, 500);
        } catch (error) {
            console.error('Export failed:', error);
            hideExportProgress();
            alert('Failed to export strategy: ' + error.message);
        }
    }, 100);
}

function showExportProgress() {
    const progressBar = document.createElement('div');
    progressBar.id = 'exportProgressBar';
    progressBar.innerHTML = `
        <div class="progress-overlay">
            <div class="progress-container">
                <div class="progress-text">Preparing export...</div>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(progressBar);
}

function hideExportProgress() {
    const progressBar = document.getElementById('exportProgressBar');
    if (progressBar) {
        progressBar.remove();
    }
}

// Import strategy from JSON file
function importPositions() {
    importFileInput.click();
}

function handleImportFile(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const importData = JSON.parse(e.target.result);

            // Validate import data structure
            if (!importData.version || !importData.players) {
                throw new Error('Invalid file format');
            }

            // Import player list first
            if (importData.players && Array.isArray(importData.players)) {
                members.length = 0; // Clear existing members
                importData.players.forEach(player => {
                    members.push({
                        id: player.id,
                        name: player.name,
                        role: player.role,
                        team: player.team,
                        weapon1: player.weapon1 || '',
                        weapon2: player.weapon2 || ''
                    });
                });
                savePlayersToStorage(); // Save to localStorage
            }

            // Clear current placements without confirmation
            const markers = mapArea.querySelectorAll('.member-marker, .group-marker, .objective-marker, .boss-marker, .tower-marker, .tree-marker, .goose-marker, .enemy-marker');
            markers.forEach(marker => marker.remove());
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
            enemiesCount = 0;

            // Import individuals
            if (importData.individuals && Array.isArray(importData.individuals)) {
                importData.individuals.forEach(placement => {
                    const member = members.find(m => m.id === placement.memberId);
                    if (member) {
                        placedMembers.push({
                            memberId: placement.memberId,
                            x: placement.x,
                            y: placement.y
                        });
                    }
                });
            }

            // Import groups
            if (importData.groups && Array.isArray(importData.groups)) {
                importData.groups.forEach(groupData => {
                    const memberIds = groupData.memberIds.filter(id => members.find(mem => mem.id === id));
                    if (memberIds.length > 0) {
                        placedGroups.push({
                            id: groupData.id || (Date.now() + Math.random()),
                            teams: groupData.teams,
                            memberIds: memberIds,
                            x: groupData.x,
                            y: groupData.y
                        });
                    }
                });
            }

            // Import objectives
            if (importData.objectives && Array.isArray(importData.objectives)) {
                importData.objectives.forEach(obj => {
                    placedObjectives.push({
                        id: obj.id,
                        x: obj.x,
                        y: obj.y
                    });
                });
            }

            // Import bosses
            if (importData.bosses && Array.isArray(importData.bosses)) {
                importData.bosses.forEach(boss => {
                    placedBosses.push({
                        id: boss.id,
                        x: boss.x,
                        y: boss.y
                    });
                });
            }

            // Import blue towers
            if (importData.blueTowers && Array.isArray(importData.blueTowers)) {
                importData.blueTowers.forEach(tower => {
                    placedBlueTowers.push({
                        id: tower.id,
                        x: tower.x,
                        y: tower.y
                    });
                });
            }

            // Import red towers
            if (importData.redTowers && Array.isArray(importData.redTowers)) {
                importData.redTowers.forEach(tower => {
                    placedRedTowers.push({
                        id: tower.id,
                        x: tower.x,
                        y: tower.y
                    });
                });
            }

            // Backwards compatibility: Import old single 'towers' array
            if (importData.towers && Array.isArray(importData.towers)) {
                importData.towers.forEach(tower => {
                    // Default old towers to blue (ally) towers
                    placedBlueTowers.push({
                        id: tower.id,
                        x: tower.x,
                        y: tower.y
                    });
                });
            }

            // Import blue trees
            if (importData.blueTrees && Array.isArray(importData.blueTrees)) {
                importData.blueTrees.forEach(tree => {
                    placedBlueTrees.push({
                        id: tree.id,
                        x: tree.x,
                        y: tree.y
                    });
                });
            }

            // Import red trees
            if (importData.redTrees && Array.isArray(importData.redTrees)) {
                importData.redTrees.forEach(tree => {
                    placedRedTrees.push({
                        id: tree.id,
                        x: tree.x,
                        y: tree.y
                    });
                });
            }

            // Backwards compatibility: Import old single 'trees' array as blue trees
            if (importData.trees && Array.isArray(importData.trees) && !importData.blueTrees && !importData.redTrees) {
                importData.trees.forEach(tree => {
                    placedBlueTrees.push({
                        id: tree.id,
                        x: tree.x,
                        y: tree.y
                    });
                });
            }

            // Import blue geese
            if (importData.blueGeese && Array.isArray(importData.blueGeese)) {
                importData.blueGeese.forEach(goose => {
                    placedBlueGeese.push({
                        id: goose.id,
                        x: goose.x,
                        y: goose.y
                    });
                });
            }

            // Import red geese
            if (importData.redGeese && Array.isArray(importData.redGeese)) {
                importData.redGeese.forEach(goose => {
                    placedRedGeese.push({
                        id: goose.id,
                        x: goose.x,
                        y: goose.y
                    });
                });
            }

            // Import enemies
            if (importData.enemies && Array.isArray(importData.enemies)) {
                importData.enemies.forEach(enemy => {
                    placedEnemies.push({
                        id: enemy.id,
                        x: enemy.x,
                        y: enemy.y,
                        count: enemy.count
                    });
                });
            }

            // Restore enemies count
            if (typeof importData.enemiesCount === 'number') {
                enemiesCount = importData.enemiesCount;
            } else {
                // Calculate from enemies array
                enemiesCount = placedEnemies.reduce((sum, enemy) => sum + (enemy.count || 0), 0);
            }

            // Import drawings
            if (importData.drawings && Array.isArray(importData.drawings)) {
                drawingPaths = [];
                drawingDeleteTimers = [];
                drawingHistory = [];
                drawingRedoStack = [];

                importData.drawings.forEach(drawing => {
                    if (drawing.points && Array.isArray(drawing.points)) {
                        drawingPaths.push({
                            id: drawing.id || Date.now() + Math.random(),
                            points: drawing.points,
                            color: drawing.color || '#ff0000',
                            width: drawing.width || 3
                        });
                    }
                });

                // Save initial drawing state to history
                if (drawingPaths.length > 0) {
                    drawingHistory = [JSON.parse(JSON.stringify(drawingPaths))];
                }

                // Redraw canvas with imported drawings
                redrawAllPaths();
            }

            // Import team name mappings (custom renamed teams)
            if (importData.teamNames && typeof importData.teamNames === 'object') {
                teamNameMappings = { ...importData.teamNames };
                saveTeamNames();
            }

            // Update display
            renderMap();
            renderMemberList();
            updateCounts();
            savePositions();

            alert('Strategy imported successfully!');
        } catch (error) {
            console.error('Error importing strategy:', error);
            alert('Failed to import strategy. Please make sure the file is valid.');
        }

        // Reset file input
        importFileInput.value = '';
    };

    reader.readAsText(file);
}

// Get formation data object
function getFormationData() {
    return {
        formationName: formationNameInput ? formationNameInput.value : '',
        members: placedMembers,
        groups: placedGroups,
        objectives: placedObjectives,
        bosses: placedBosses,
        blueTowers: placedBlueTowers,
        redTowers: placedRedTowers,
        blueTrees: placedBlueTrees,
        redTrees: placedRedTrees,
        blueGeese: placedBlueGeese,
        redGeese: placedRedGeese,
        enemies: placedEnemies,
        // Add roster data
        roster: {
            teams: teams,
            assignments: members.reduce((acc, m) => {
                acc[m.id] = m.team;
                return acc;
            }, {})
        },
        // Add drawings
        drawings: window.drawingPaths || []
    };
}

// Save positions to localStorage
function savePositions() {
    const data = getFormationData();
    localStorage.setItem('vcross-gvg-positions', JSON.stringify(data));
}

// Save formation to server
function saveFormationToServer() {
    if (!window.saveFormationUrl) return;

    const data = getFormationData();
    const btn = document.getElementById('saveFormationBtn');
    if (!btn) return;

    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;

    fetch(window.saveFormationUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        },
        body: JSON.stringify({ formation_data: data })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
             btn.innerHTML = '<i class="fas fa-check"></i> Saved';
             btn.classList.remove('btn-success');
             btn.classList.add('btn-primary');
             setTimeout(() => {
                 btn.innerHTML = originalContent;
                 btn.disabled = false;
                 btn.classList.remove('btn-primary');
                 btn.classList.add('btn-success');
             }, 2000);
        } else {
            alert('Failed to save formation');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error saving formation:', error);
        alert('Error saving formation');
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
}

// Clear all placements from map (helper for loading new data)
function clearAllPlacements() {
    const markers = mapArea.querySelectorAll('.member-marker, .group-marker, .objective-marker, .boss-marker, .tower-marker, .tree-marker, .goose-marker, .enemy-marker');
    markers.forEach(marker => marker.remove());
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
    enemiesCount = 0;
}

// Load saved positions
function loadSavedPositions(externalData = null) {
    if (externalData) {
        clearAllPlacements();
    }

    let savedData = externalData;

    if (!savedData) {
        // Prioritize server data if available (only on first load)
        if (window.initialFormationData && typeof window.initialFormationData === 'object' && Object.keys(window.initialFormationData).length > 0) {
            savedData = window.initialFormationData;
            console.log('Loaded formation from server');
        } else {
            const localSaved = localStorage.getItem('vcross-gvg-positions');
            if (localSaved) {
                try {
                    savedData = JSON.parse(localSaved);
                    console.log('Loaded formation from localStorage');
                } catch (e) {
                    console.error('Error parsing localStorage data', e);
                }
            }
        }
    }

    if (savedData) {
        try {
            const data = savedData;
            console.log('Applying saved formation data:', data);

            // Load formation name
            if (formationNameInput) {
                formationNameInput.value = data.formationName || '';
            }

            // Support old format (just array of members)
            if (Array.isArray(data)) {
                placedMembers = [];
                data.forEach(placement => {
                    const member = members.find(m => m.id === placement.memberId);
                    if (member && getTotalPlacedPlayers() < MAX_PLAYERS) {
                        placeMemberOnMap(member, placement.x, placement.y);
                    }
                });
            } else {
                // New format with members and groups
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

                // Load roster data if available
                if (data.roster) {
                    if (data.roster.teams && Array.isArray(data.roster.teams)) {
                        teams = data.roster.teams;
                    }

                    if (data.roster.assignments) {
                        const assignments = data.roster.assignments;
                        members.forEach(m => {
                            if (assignments[m.id]) {
                                m.team = assignments[m.id];
                            }
                        });
                    }
                } else if (data.teamNames) {
                     // Backward compatibility: load team names into default teams
                     // If teams is empty (which is now default), we need to recreate the default teams if we are loading legacy data
                     if (teams.length === 0) {
                         teams = [
                            { id: 'Team 1', name: 'Team 1', captainId: null, description: '' },
                            { id: 'Team 2', name: 'Team 2', captainId: null, description: '' },
                            { id: 'Team 3', name: 'Team 3', captainId: null, description: '' },
                            { id: 'Team 4', name: 'Team 4', captainId: null, description: '' },
                            { id: 'Team 5', name: 'Team 5', captainId: null, description: '' },
                            { id: 'Team 6', name: 'Team 6', captainId: null, description: '' }
                        ];
                     }

                     teamNameMappings = data.teamNames;
                     teams.forEach(t => {
                         if (teamNameMappings[t.id]) {
                             t.name = teamNameMappings[t.id];
                         }
                     });
                }

                // Load individual members
                if (data.members) {
                    data.members.forEach(placement => {
                        const member = members.find(m => m.id === placement.memberId);
                        if (member && getTotalPlacedPlayers() < MAX_PLAYERS) {
                            placeMemberOnMap(member, placement.x, placement.y);
                        }
                    });
                }

                // Load groups
                if (data.groups) {
                    data.groups.forEach(groupData => {
                        if (getTotalPlacedPlayers() + groupData.memberIds.length <= MAX_PLAYERS) {
                            placedGroups.push(groupData);
                            renderGroupMarker(groupData);
                        }
                    });
                }

                // Load objectives
                if (data.objectives) {
                    data.objectives.forEach(obj => {
                        placeObjectiveMarker(obj.x, obj.y);
                    });
                }

                // Load bosses
                if (data.bosses) {
                    data.bosses.forEach(boss => {
                        placeBossMarker(boss.x, boss.y);
                    });
                }

                // Load blue towers
                if (data.blueTowers) {
                    data.blueTowers.forEach(tower => {
                        placeBlueTowerMarker(tower.x, tower.y);
                    });
                }

                // Load red towers
                if (data.redTowers) {
                    data.redTowers.forEach(tower => {
                        placeRedTowerMarker(tower.x, tower.y);
                    });
                }

                // Backwards compatibility: Load old single 'towers' array as blue towers
                if (data.towers && !data.blueTowers && !data.redTowers) {
                    data.towers.forEach(tower => {
                        placeBlueTowerMarker(tower.x, tower.y);
                    });
                }

                // Load blue trees
                if (data.blueTrees) {
                    data.blueTrees.forEach(tree => {
                        placeBlueTreeMarker(tree.x, tree.y);
                    });
                }

                // Load red trees
                if (data.redTrees) {
                    data.redTrees.forEach(tree => {
                        placeRedTreeMarker(tree.x, tree.y);
                    });
                }

                // Backwards compatibility: Load old single 'trees' array as blue trees
                if (data.trees && !data.blueTrees && !data.redTrees) {
                    data.trees.forEach(tree => {
                        placeBlueTreeMarker(tree.x, tree.y);
                    });                }

                // Load blue geese
                if (data.blueGeese) {
                    data.blueGeese.forEach(goose => {
                        placeBlueGooseMarker(goose.x, goose.y);
                    });
                }

                // Load red geese
                if (data.redGeese) {
                    data.redGeese.forEach(goose => {
                        placeRedGooseMarker(goose.x, goose.y);
                    });
                }

                // Load enemies
                if (data.enemies) {
                    data.enemies.forEach(enemy => {
                        placeEnemyGroup(enemy.x, enemy.y);
                    });
                }

                // Load drawings
                if (data.drawings && Array.isArray(data.drawings)) {
                    window.drawingPaths = data.drawings;
                    if (window.redrawAllPaths) {
                        window.redrawAllPaths();
                    }
                }
            }

            updateEnemyCount();

            // Re-render lists to reflect team changes and assignments
            renderMemberList();
            renderPlayerManagementList();
            updateCounts();

        } catch (e) {
            console.error('Error loading saved positions:', e);
        }
    }
}

// Setup click outside handler to close split view
function setupClickOutsideHandler() {
    document.addEventListener('click', function(e) {
        // If no split view is active, do nothing
        if (!activeSplitGroupId) return;

        // Check if click is on a group marker or its children (tooltip, buttons, etc.)
        const clickedMarker = e.target.closest('.group-marker');
        if (clickedMarker) {
            // If clicking on the same group marker, let toggleSplitView handle it
            const clickedGroupId = clickedMarker.dataset.groupId;
            if (clickedGroupId === activeSplitGroupId) {
                return; // Let the button click handle toggling
            }
        }

        // Check if click is on a draggable item being dragged
        if (e.target.closest('[draggable="true"]') && e.target.closest('[draggable="true"]').style.opacity === '0.5') {
            return; // Don't close while dragging
        }

        // Check if click is on a split member item (for dragging)
        if (e.target.closest('.split-member-item')) {
            return; // Allow interaction with split members
        }

        // Click is outside - close the split view
        const splitDiv = document.getElementById(`split-${activeSplitGroupId}`);
        if (splitDiv) {
            splitDiv.style.display = 'none';
        }
        activeSplitGroupId = null;
    });
}

// Player Management Functions
function setupPlayerManagementHandlers() {
    // Open player management modal
    if (managePlayersBtn) {
        managePlayersBtn.addEventListener('click', openPlayerManagementModal);
    }

    // Close modals
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closePlayerManagementModal);
    }
    if (closeEditModalBtn) {
        closeEditModalBtn.addEventListener('click', closePlayerEditModal);
    }

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (playerManagementModal && e.target === playerManagementModal) {
            closePlayerManagementModal();
        }
        if (playerEditModal && e.target === playerEditModal) {
            closePlayerEditModal();
        }
    });

    // Add new player button
    if (addNewPlayerBtn) {
        addNewPlayerBtn.addEventListener('click', () => {
            openPlayerEditModal();
        });
    }

    // Cancel edit button
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', closePlayerEditModal);
    }

    // Form submission
    if (playerEditForm) {
        playerEditForm.addEventListener('submit', handlePlayerFormSubmit);
    }
}

// ============================================================================
// PLAYER MANAGEMENT (CRUD Operations)
// ============================================================================

function openPlayerManagementModal() {
    renderPlayerManagementList();
    playerManagementModal.style.display = 'flex';
}

function closePlayerManagementModal() {
    playerManagementModal.style.display = 'none';
}

function openPlayerEditModal(playerId = null) {
    const editModalTitle = document.getElementById('editModalTitle');
    const editPlayerId = document.getElementById('editPlayerId');
    const editPlayerName = document.getElementById('editPlayerName');
    const editPlayerRole = document.getElementById('editPlayerRole');
    const editPlayerTeam = document.getElementById('editPlayerTeam');
    const editPlayerWeapon1 = document.getElementById('editPlayerWeapon1');
    const editPlayerWeapon2 = document.getElementById('editPlayerWeapon2');

    // Populate team dropdown dynamically with current team names (including renamed ones)
    editPlayerTeam.innerHTML = '';
    if (teams.length > 0) {
        teams.forEach(team => {
            const option = document.createElement('option');
            option.value = team.id;
            option.textContent = getTeamDisplayName(team.id);
            editPlayerTeam.appendChild(option);
        });
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Unassigned';
        editPlayerTeam.appendChild(option);
    }

    if (playerId) {
        // Edit existing player
        const player = members.find(m => m.id === playerId);
        if (player) {
            editModalTitle.textContent = 'Edit Player';
            editPlayerId.value = playerId;
            editPlayerName.value = player.name;
            editPlayerRole.value = player.role;
            editPlayerTeam.value = player.team;
            editPlayerWeapon1.value = player.weapon1 || 'Nameless Sword';
            editPlayerWeapon2.value = player.weapon2 || 'Nameless Spear';
        }
    } else {
        // Add new player
        editModalTitle.textContent = 'Add New Player';
        editPlayerId.value = '';
        editPlayerName.value = '';
        editPlayerRole.value = 'DPS';
        editPlayerTeam.value = teams.length > 0 ? teams[0].id : '';
        editPlayerWeapon1.value = 'Nameless Sword';
        editPlayerWeapon2.value = 'Nameless Spear';
    }

    playerEditModal.style.display = 'flex';
    editPlayerName.focus();
}

function closePlayerEditModal() {
    playerEditModal.style.display = 'none';
    playerEditForm.reset();
}

function handlePlayerFormSubmit(e) {
    e.preventDefault();

    const editPlayerId = document.getElementById('editPlayerId');
    const editPlayerName = document.getElementById('editPlayerName');
    const editPlayerRole = document.getElementById('editPlayerRole');
    const editPlayerTeam = document.getElementById('editPlayerTeam');
    const editPlayerWeapon1 = document.getElementById('editPlayerWeapon1');
    const editPlayerWeapon2 = document.getElementById('editPlayerWeapon2');

    const playerId = editPlayerId.value ? parseInt(editPlayerId.value) : null;
    const playerName = editPlayerName.value.trim();
    const playerRole = editPlayerRole.value;
    const playerTeam = editPlayerTeam.value;
    const playerWeapon1 = editPlayerWeapon1.value;
    const playerWeapon2 = editPlayerWeapon2.value;

    if (!playerName) {
        alert('Player name is required!');
        return;
    }

    if (playerId) {
        // Edit existing player
        const player = members.find(m => m.id === playerId);
        if (player) {
            player.name = playerName;
            player.role = playerRole;
            player.team = playerTeam;
            player.weapon1 = playerWeapon1;
            player.weapon2 = playerWeapon2;

            // Update any placed markers for this player
            updatePlacedPlayerInfo(playerId);
        }
    } else {
        // Add new player
        if (members.length >= MAX_PLAYERS) {
            alert(`Maximum ${MAX_PLAYERS} players allowed!`);
            return;
        }

        const newId = members.length > 0 ? Math.max(...members.map(m => m.id)) + 1 : 1;
        const newPlayer = {
            id: newId,
            name: playerName,
            role: playerRole,
            team: playerTeam,
            weapon1: playerWeapon1,
            weapon2: playerWeapon2
        };

        members.push(newPlayer);
    }

    // Save to localStorage
    savePlayersToStorage();

    // Re-render lists
    renderMemberList();
    renderPlayerManagementList();
    updateCounts();

    closePlayerEditModal();
}

function renderPlayerManagementList() {
    playerManagementList.innerHTML = '';

    // Filter members that are NOT in any valid team
    const validTeamIds = teams.map(t => t.id);
    const unassignedMembers = members.filter(m => !m.team || !validTeamIds.includes(m.team));

    if (unassignedMembers.length === 0) {
        playerManagementList.innerHTML = `<div class="no-players" style="text-align: center; padding: 20px; color: #718096;">${GW_CONSTANTS.PLAYER_MANAGEMENT.NO_PLAYERS}</div>`;
        return;
    }

    // Set grid layout for the list container
    playerManagementList.style.display = 'grid';
    playerManagementList.style.gridTemplateColumns = 'repeat(auto-fill, minmax(280px, 1fr))';
    playerManagementList.style.gap = '15px';
    playerManagementList.style.padding = '10px';

    unassignedMembers.forEach(member => {
        const playerItem = document.createElement('div');
        playerItem.className = 'player-management-card';
        
        // Card Styling
        Object.assign(playerItem.style, {
            background: 'white',
            border: '1px solid #e2e8f0',
            borderRadius: '8px',
            padding: '15px',
            boxShadow: '0 2px 4px rgba(0,0,0,0.05)',
            display: 'flex',
            flexDirection: 'column',
            gap: '12px',
            transition: 'transform 0.2s, box-shadow 0.2s'
        });

        // Hover effect
        playerItem.onmouseenter = () => {
            playerItem.style.transform = 'translateY(-2px)';
            playerItem.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
        };
        playerItem.onmouseleave = () => {
            playerItem.style.transform = 'none';
            playerItem.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
        };

        // Build team options
        let teamOptions = '';
        teams.forEach(t => {
            teamOptions += `<option value="${t.id}">${t.name}</option>`;
        });

        playerItem.innerHTML = `
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #f0f0f0; padding-bottom: 8px; margin-bottom: 0;">
                <div>
                    <div style="font-weight: bold; font-size: 1.1rem; color: #2d3748;">${member.name}</div>
                </div>
                <span class="role-badge-small role-${member.role}" style="font-size: 0.8rem; padding: 4px 8px; border-radius: 12px; background: #edf2f7; font-weight: 600;">${member.role}</span>
            </div>
            
            <div class="card-body" style="font-size: 0.9rem; color: #4a5568;">
                <div style="margin-bottom: 5px; display: flex; align-items: center;">
                    <span style="margin-right: 8px;">⚔️</span>
                    <span title="${GW_CONSTANTS.PLAYER_MANAGEMENT.WEAPON_LABEL} 1">${member.weapon1 || 'N/A'}</span>
                </div>
                <div style="display: flex; align-items: center;">
                    <span style="margin-right: 8px;">🛡️</span>
                    <span title="${GW_CONSTANTS.PLAYER_MANAGEMENT.WEAPON_LABEL} 2">${member.weapon2 || 'N/A'}</span>
                </div>
            </div>

            <div class="card-footer" style="margin-top: auto; padding-top: 10px; border-top: 1px solid #f0f0f0; display: flex; gap: 8px;">
                <select class="form-control form-control-sm team-select-${member.id}" style="flex: 1; border-color: #cbd5e0;">
                    ${teamOptions}
                </select>
                <button class="btn btn-sm btn-primary" onclick="assignMemberToTeam(${member.id})" style="white-space: nowrap; px: 15px;">
                    <i class="fas fa-plus"></i> ${GW_CONSTANTS.PLAYER_MANAGEMENT.ADD_BUTTON}
                </button>
            </div>
        `;

        playerManagementList.appendChild(playerItem);
    });
}

function assignMemberToTeam(memberId) {
    const member = members.find(m => m.id === memberId);
    if (!member) return;

    const select = document.querySelector(`.team-select-${memberId}`);
    if (!select) return;

    const teamId = select.value;
    if (!teamId) return;

    member.team = teamId;

    // If this member was captain of another team (unlikely if unassigned, but safe check), handle it
    // Actually, if they are unassigned, they shouldn't be captain.

    savePositions();
    renderMemberList();
    renderPlayerManagementList();
    updateCounts();
}

function removeMemberFromTeam(memberId) {
    const member = members.find(m => m.id === memberId);
    if (!member) return;

    // Check if member is captain
    const team = teams.find(t => t.id === member.team);
    if (team && team.captainId === member.id) {
        team.captainId = null;
    }

    // Remove from team
    member.team = null;

    // Save changes
    savePositions();

    // Update UI
    renderMemberList();
    renderPlayerManagementList();
    updateCounts();

    // If member was placed on map, update their info
    if (isPlayerPlaced(memberId)) {
        updatePlacedPlayerInfo(memberId);
    }
}

function deletePlayer(playerId) {
    const player = members.find(m => m.id === playerId);
    if (!player) return;

    const isPlaced = isPlayerPlaced(playerId);

    let confirmMsg = `Are you sure you want to delete ${player.name}?`;
    if (isPlaced) {
        confirmMsg += '\n\nThis player is currently placed on the map and will be removed.';
    }

    if (!confirm(confirmMsg)) {
        return;
    }

    // Remove from placed members
    if (isPlaced) {
        removeMemberMarker(playerId);

        // Remove from groups
        placedGroups.forEach(group => {
            const index = group.memberIds.indexOf(playerId);
            if (index > -1) {
                group.memberIds.splice(index, 1);

                const marker = mapArea.querySelector(`[data-group-id="${group.id}"]`);
                if (marker) {
                    if (group.memberIds.length === 0) {
                        marker.remove();
                        placedGroups = placedGroups.filter(g => g.id !== group.id);
                    } else {
                        updateGroupMarker(marker, group);
                    }
                }
            }
        });
    }

    // Remove from members array
    members = members.filter(m => m.id !== playerId);

    // Save and refresh
    savePlayersToStorage();
    savePositions();
    renderMemberList();
    renderPlayerManagementList();
    updateCounts();
}

function updatePlacedPlayerInfo(playerId) {
    // Update individual marker tooltips
    const marker = mapArea.querySelector(`[data-member-id="${playerId}"]`);
    if (marker) {
        const member = members.find(m => m.id === playerId);
        if (member) {
            marker.className = `member-marker role-${member.role}`;
            const tooltip = marker.querySelector('.marker-tooltip .tooltip-info');
            if (tooltip) {
                const displayTeamName = getTeamDisplayName(member.team);
                tooltip.textContent = `${member.role} | ${displayTeamName}`;
            }
            const weaponsTooltip = marker.querySelector('.marker-tooltip .tooltip-weapons');
            if (weaponsTooltip) {
                weaponsTooltip.textContent = `⚔️ ${member.weapon1 || 'N/A'} | ${member.weapon2 || 'N/A'}`;
            } else if (tooltip) {
                // Add weapons tooltip if it doesn't exist
                const weaponsDiv = document.createElement('div');
                weaponsDiv.className = 'tooltip-weapons';
                weaponsDiv.textContent = `⚔️ ${member.weapon1 || 'N/A'} | ${member.weapon2 || 'N/A'}`;
                tooltip.parentElement.appendChild(weaponsDiv);
            }
        }
    }

    // Update group markers that contain this player
    placedGroups.forEach(group => {
        if (group.memberIds.includes(playerId)) {
            const groupMarker = mapArea.querySelector(`[data-group-id="${group.id}"]`);
            if (groupMarker) {
                updateGroupMarker(groupMarker, group);
            }
        }
    });
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

// Panel toggle functionality
function savePlayersToStorage() {
    localStorage.setItem('vcross-gvg-players', JSON.stringify(members));
}


// Migrate old team names to new team names
function migrateTeamNames(members) {
    const teamNameMap = {
        'FrontLine': 'Team 1',
        'Jungle': 'Team 2',
        'Defence 1': 'Team 3',
        'Defence 2': 'Team 4',
        'Backline 1': 'Team 5',
        'Backline 2': 'Team 6'
    };

    return members.map(member => {
        let updatedMember = { ...member };

        // Migrate team names
        if (teamNameMap[member.team]) {
            updatedMember.team = teamNameMap[member.team];
        }

        // Migrate Support role to DPS
        if (member.role === 'Support') {
            updatedMember.role = 'DPS';
        }

        return updatedMember;
    });
}
function loadPlayersFromStorage() {
    // Prioritize Laravel injected data
    if (window.initialMembers && Array.isArray(window.initialMembers)) {
        members = window.initialMembers;

        // Migrate team names if needed (though server data should be clean)
        // members = migrateTeamNames(members);

        // Update local storage to match server data
        savePlayersToStorage();
        return;
    }

    const saved = localStorage.getItem('vcross-gvg-players');
    if (saved) {
        try {
            let loadedMembers = JSON.parse(saved);
            if (Array.isArray(loadedMembers) && loadedMembers.length > 0) {
                // Migrate old team names to new team names
                loadedMembers = migrateTeamNames(loadedMembers);
                members = loadedMembers;
                // Save the migrated data back to storage
                savePlayersToStorage();
            }
        } catch (e) {
            console.error('Error loading players:', e);
            // If there's an error, save the default data
            savePlayersToStorage();
        }
    } else {
        // No saved data, save the default data from data.js
        savePlayersToStorage();
    }
}

// ============================================================================
// THEME FUNCTIONS
// ============================================================================

function toggleTheme() {
    document.body.classList.toggle('dark-theme');
    const isDark = document.body.classList.contains('dark-theme');

    // Update icon
    const icon = themeToggleBtn.querySelector('.theme-icon');
    icon.textContent = isDark ? '☀️' : '🌙';

    // Save preference
    localStorage.setItem('vcross-gvg-theme', isDark ? 'dark' : 'light');
}

function loadThemePreference() {
    const savedTheme = localStorage.getItem('vcross-gvg-theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        const icon = themeToggleBtn.querySelector('.theme-icon');
        icon.textContent = '☀️';
    }
}

// Initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
