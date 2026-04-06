<template>
  <div v-if="isVisible" class="modal-backdrop-custom">
    <div class="modal-dialog-custom">
      <div class="card card-primary h-100 mb-0">
        <div class="card-header">
          <h3 class="card-title">{{ title || t('select_members') }}</h3>
          <div class="card-tools">
            <span class="badge badge-light">{{ users.length }} {{ t('available') }}</span>
            <button type="button" class="btn btn-tool" @click="close">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        
        <div class="card-body d-flex flex-column">
          <div class="row mb-3 align-items-center">
            <div class="col-md-6">
              <input 
                type="text" 
                v-model="searchQuery" 
                class="form-control" 
                :placeholder="t('search_placeholder')"
              >
            </div>
            <div class="col-md-6 text-right">
              <div class="custom-control custom-checkbox">
                <input 
                  type="checkbox" 
                  class="custom-control-input" 
                  id="selectAllVueSelector"
                  :checked="isAllVisibleSelected"
                  @change="toggleSelectAll"
                >
                <label class="custom-control-label" for="selectAllVueSelector">{{ t('select_all_visible') }}</label>
              </div>
            </div>
          </div>

          <div class="row user-grid flex-grow-1 align-content-start" style="overflow-y: auto;">
            <div 
              v-for="user in filteredUsers" 
              :key="user.id" 
              class="col-xl-2 col-lg-3 col-md-4 col-6 mb-3"
            >
              <div 
                class="card user-card h-100" 
                :class="{ 'selected': isSelected(user.id) }"
                @click="toggleUser(user.id)"
              >
                <div class="check-indicator">
                  <i v-if="!isSelected(user.id)" class="far fa-circle text-muted"></i>
                  <i v-else class="fas fa-check-circle text-primary"></i>
                </div>
                
                <div class="card-body text-center p-3">
                  <div class="user-avatar-container">
                    <img 
                      :src="user.discord_avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}`" 
                      class="user-avatar" 
                      alt="Avatar"
                    >
                  </div>

                  <h5 class="font-weight-bold mb-1 user-account">{{ user.account || user.name }}</h5>
                  <p class="text-muted small mb-0 user-ingame">{{ user.ingame_name || t('not_available') }}</p>
                </div>
              </div>
            </div>
            
            <div v-if="filteredUsers.length === 0" class="col-12 text-center py-5">
              <p class="text-muted">{{ t('no_participants_found') }}</p>
            </div>
          </div>
        </div>
        
        <div class="card-footer text-right">
          <button type="button" class="btn btn-secondary mr-2" @click="close">{{ t('cancel') }}</button>
          <button type="button" class="btn btn-primary" :disabled="selectedIds.length === 0" @click="confirmSelection">
            {{ t('add') }} <span class="badge badge-light ml-1">{{ selectedIds.length }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    translations: {
      type: Object,
      default: () => ({
        select_members: 'Select Members',
        available: 'Available',
        search_placeholder: 'Search...',
        select_all_visible: 'Select All Visible',
        no_participants_found: 'No participants found',
        cancel: 'Cancel',
        add: 'Add',
        add_members_to_team: 'Add members to team :team'
      })
    }
  },
  data() {
    return {
      isVisible: false,
      teamId: null,
      users: [],
      searchQuery: '',
      selectedIds: [],
      title: '',
    };
  },
  computed: {
    filteredUsers() {
      if (!this.searchQuery) return this.users;
      const query = this.searchQuery.toLowerCase();
      return this.users.filter(user => {
        const account = (user.account || user.name || '').toLowerCase();
        const ingame = (user.ingame_name || '').toLowerCase();
        return account.includes(query) || ingame.includes(query);
      });
    },
    isAllVisibleSelected() {
      if (this.filteredUsers.length === 0) return false;
      return this.filteredUsers.every(user => this.selectedIds.includes(user.id));
    }
  },
  mounted() {
    // Expose method to global window object
    window.openTeamMemberSelector = (teamId, unassignedMembers, teamName) => {
      this.teamId = teamId;
      this.users = unassignedMembers || [];
      if (teamName) {
        const tpl = this.t('add_members_to_team');
        this.title = (tpl || '').replace(':team', teamName);
      } else {
        this.title = '';
      }
      
      this.selectedIds = [];
      this.searchQuery = '';
      this.isVisible = true;
    };
  },
  methods: {
    t(key) {
      return this.translations[key] || key;
    },
    close() {
      this.isVisible = false;
      this.selectedIds = [];
      this.teamId = null;
    },
    isSelected(userId) {
      return this.selectedIds.includes(userId);
    },
    toggleUser(userId) {
      const index = this.selectedIds.indexOf(userId);
      if (index === -1) {
        this.selectedIds.push(userId);
      } else {
        this.selectedIds.splice(index, 1);
      }
    },
    toggleSelectAll() {
      const allVisibleIds = this.filteredUsers.map(u => u.id);
      
      if (this.isAllVisibleSelected) {
        // Deselect all visible
        this.selectedIds = this.selectedIds.filter(id => !allVisibleIds.includes(id));
      } else {
        // Select all visible (add missing ones)
        allVisibleIds.forEach(id => {
          if (!this.selectedIds.includes(id)) {
            this.selectedIds.push(id);
          }
        });
      }
    },
    confirmSelection() {
      if (this.teamId && this.selectedIds.length > 0) {
        // Call global function to handle addition
        if (window.addMembersToTeam) {
          window.addMembersToTeam(this.teamId, [...this.selectedIds]);
        }
        this.close();
      }
    }
  }
};
</script>

<style scoped>
.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.modal-dialog-custom {
  width: 90%;
  max-width: 1000px;
  height: 85vh;
  display: flex;
  flex-direction: column;
}

.user-card {
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  border: 1px solid #ddd;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
}

.user-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.user-card.selected {
  border-color: #007bff;
  background-color: #f8f9fa;
}

.check-indicator {
  position: absolute;
  top: 10px;
  right: 10px;
  font-size: 1.2rem;
  z-index: 10;
}

.user-avatar-container {
  width: 100%;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.5rem;
  overflow: hidden;
}

.user-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #dee2e6;
}

.user-card.selected .user-avatar {
  border-color: #007bff;
}

.user-account {
  font-size: 1rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

.user-ingame {
  font-size: 0.85rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}
</style>
