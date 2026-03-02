<template>
  <div class="add-participants-container">
    <div class="card card-primary">
      <div class="card-header">
        <h3 class="card-title">{{ title }}</h3>
        <div class="card-tools">
          <span class="badge badge-light">{{ users.length }} {{ translations.members_count }}</span>
        </div>
      </div>
      
      <form :action="submitUrl" method="POST">
        <input type="hidden" name="_token" :value="csrfToken">
        
        <div class="card-body">
          <div class="row mb-3 align-items-center">
            <div class="col-md-6">
              <input 
                type="text" 
                v-model="searchQuery" 
                class="form-control" 
                :placeholder="translations.search_players"
              >
            </div>
            <div class="col-md-6 text-right">
              <div class="custom-control custom-checkbox">
                <input 
                  type="checkbox" 
                  class="custom-control-input" 
                  id="selectAllVue"
                  :checked="isAllVisibleSelected"
                  @change="toggleSelectAll"
                >
                <label class="custom-control-label" for="selectAllVue">{{ translations.select_all_visible }}</label>
              </div>
            </div>
          </div>

          <div class="row user-grid" style="max-height: 600px; overflow-y: auto;">
            <div 
              v-for="user in filteredUsers" 
              :key="user.id" 
              class="col-lg-3 col-md-4 col-sm-6 mb-3"
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
                  <p class="text-muted small mb-0 user-ingame">{{ user.ingame_name || translations.not_available }}</p>
                </div>
              </div>
            </div>
            
            <div v-if="filteredUsers.length === 0" class="col-12 text-center py-5">
              <p class="text-muted">{{ translations.no_participants_found || 'No participants found' }}</p>
            </div>
          </div>
        </div>
        
        <!-- Hidden inputs for ALL selected IDs -->
        <input v-for="id in selectedIds" :key="id" type="hidden" name="participant_ids[]" :value="id">
        
        <div class="card-footer">
          <button type="submit" class="btn btn-primary" :disabled="selectedIds.length === 0">
            {{ translations.add }} <span class="badge badge-light ml-1">{{ selectedIds.length }}</span>
          </button>
          <a :href="cancelUrl" class="btn btn-secondary ml-2">{{ translations.cancel }}</a>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    initialUsers: {
      type: Array,
      required: true
    },
    title: {
      type: String,
      default: ''
    },
    submitUrl: {
      type: String,
      required: true
    },
    cancelUrl: {
      type: String,
      required: true
    },
    csrfToken: {
      type: String,
      required: true
    },
    translations: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      users: this.initialUsers,
      searchQuery: '',
      selectedIds: []
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
  methods: {
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
    }
  }
};
</script>

<style scoped>
.user-card {
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  border: 1px solid #ddd;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
}
.user-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.user-card .check-indicator {
  position: absolute;
  top: 10px;
  right: 10px;
  font-size: 1.2rem;
  z-index: 10;
}
.user-card.selected {
  border: 2px solid #007bff !important;
  background-color: #e8f0fe !important;
  box-shadow: 0 4px 12px rgba(0,123,255,0.2) !important;
}
.user-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 10px;
  border: 2px solid #ddd;
}
.user-card.selected .user-avatar {
  border-color: #007bff;
}
</style>
