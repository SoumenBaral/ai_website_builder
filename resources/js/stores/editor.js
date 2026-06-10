import { defineStore } from 'pinia';

export const useEditor = defineStore('editor', {
    state: () => ({
        schema: null,
        selectedId: null,
        history: [],
        future: [],
        dirty: false,
        saving: false,
    }),

    getters: {
        selectedBlock(state) {
            if (!state.schema || !state.selectedId) return null;
            return state.schema.sections.find(s => s.id === state.selectedId) || null;
        },
        canUndo(state) {
            return state.history.length > 0;
        },
        canRedo(state) {
            return state.future.length > 0;
        },
    },

    actions: {
        setSchema(schema) {
            this.pushHistory();
            this.schema = JSON.parse(JSON.stringify(schema));
            this.dirty = true;
        },

        select(id) {
            this.selectedId = id;
        },

        updateProps(id, patch) {
            this.pushHistory();
            const block = this.schema.sections.find(s => s.id === id);
            if (block) {
                block.props = { ...block.props, ...patch };
                this.dirty = true;
            }
        },

        updateTheme(patch) {
            this.pushHistory();
            this.schema.theme = { ...this.schema.theme, ...patch };
            this.dirty = true;
        },

        updateMeta(patch) {
            this.pushHistory();
            this.schema.meta = { ...this.schema.meta, ...patch };
            this.dirty = true;
        },

        reorder(newSections) {
            this.pushHistory();
            this.schema.sections = newSections;
            this.dirty = true;
        },

        addBlock(block, index) {
            this.pushHistory();
            if (index !== undefined) {
                this.schema.sections.splice(index, 0, block);
            } else {
                this.schema.sections.push(block);
            }
            this.dirty = true;
            this.selectedId = block.id;
        },

        updateSection(id, newSection) {
            this.pushHistory();
            const idx = this.schema.sections.findIndex(s => s.id === id);
            if (idx !== -1) {
                this.schema.sections[idx] = newSection;
                this.dirty = true;
            }
        },

        removeBlock(id) {
            this.pushHistory();
            this.schema.sections = this.schema.sections.filter(s => s.id !== id);
            if (this.selectedId === id) {
                this.selectedId = null;
            }
            this.dirty = true;
        },

        pushHistory() {
            if (this.schema) {
                this.history.push(JSON.parse(JSON.stringify(this.schema)));
                this.future = [];
                // Keep history manageable
                if (this.history.length > 50) {
                    this.history.shift();
                }
            }
        },

        undo() {
            if (this.history.length) {
                this.future.push(JSON.parse(JSON.stringify(this.schema)));
                this.schema = this.history.pop();
                this.dirty = true;
            }
        },

        redo() {
            if (this.future.length) {
                this.history.push(JSON.parse(JSON.stringify(this.schema)));
                this.schema = this.future.pop();
                this.dirty = true;
            }
        },

        markClean() {
            this.dirty = false;
            this.saving = false;
        },
    },
});
