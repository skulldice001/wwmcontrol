<template>
  <div class="inner-ways-container">
    <div
      v-for="(iw, index) in visibleInnerWays"
      :key="index"
      class="d-inline-block text-center mr-2 position-relative inner-way-item"
      :title="iw.name"
    >
      <img
        :src="iw.icon"
        :alt="iw.name"
        width="32"
        height="32"
        class="img-fluid"
      />
      <span
        class="badge badge-light border"
        style="position: absolute; bottom: -5px; right: -5px; font-size: 10px; padding: 2px 4px;"
      >
        {{ iw.level }}
      </span>
    </div>
    <button
      v-if="hiddenCount > 0"
      type="button"
      class="btn btn-link p-0 align-baseline inner-ways-toggle"
      @click="toggle"
    >
      {{ collapsed ? '+' + hiddenCount + ' more' : 'Show less' }}
    </button>
  </div>
</template>

<script>
export default {
  name: 'InnerWaysCell',
  props: {
    innerWays: {
      type: Array,
      default: () => [],
    },
    limit: {
      type: Number,
      default: 10,
    },
  },
  data() {
    return {
      collapsed: true,
    };
  },
  computed: {
    sortedInnerWays() {
      return [...this.innerWays].sort((a, b) => (b.level || 0) - (a.level || 0));
    },
    visibleInnerWays() {
      if (this.collapsed) {
        return this.sortedInnerWays.slice(0, this.limit);
      }
      return this.sortedInnerWays;
    },
    hiddenCount() {
      return Math.max(this.sortedInnerWays.length - this.limit, 0);
    },
  },
  methods: {
    toggle() {
      this.collapsed = !this.collapsed;
    },
  },
};
</script>

