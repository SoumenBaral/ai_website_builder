import { defineStore } from 'pinia';
import { markRaw, toRaw } from 'vue';

/** Edits to the same field within this window share one undo step (typing, colour pickers). */
const GROUP_MS = 700;

const snapshot = (schema) => markRaw(JSON.parse(JSON.stringify(toRaw(schema))));

export const useEditor = defineStore('editor', {
    state: () => ({
        schema: null,
        selectedId: null,
        history: [],
        future: [],
        dirty: false,
        saving: false,
        // Bumped on every change; a save only marks the editor clean if nothing changed meanwhile.
        revision: 0,
        // True while a full-site AI edit streams: the canvas is read-only so nothing typed gets lost.
        locked: false,
        lastGroup: null,
        lastGroupAt: 0,
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
        /**
         * Open a page in the editor. The store outlives Inertia page visits, so
         * history from a previously opened project must be cleared — otherwise
         * undo could restore another project's content (and autosave it here).
         */
        load(schema) {
            this.schema = JSON.parse(JSON.stringify(schema));
            this.history = [];
            this.future = [];
            this.selectedId = null;
            this.dirty = false;
            this.saving = false;
            this.locked = false;
            this.revision = 0;
            this.lastGroup = null;
        },

        setSchema(schema) {
            this.pushHistory();
            this.schema = JSON.parse(JSON.stringify(schema));
            this.touch();
        },

        /** Every mutation ends here: marks the page unsaved and invalidates in-flight saves. */
        touch() {
            this.dirty = true;
            this.revision++;
        },

        select(id) {
            this.selectedId = id;
        },

        updateProps(id, patch) {
            const block = this.schema.sections.find(s => s.id === id);
            if (!block) return;
            // Unchanged value (e.g. a contenteditable blur with no edit): not a change at all.
            if (Object.keys(patch).every((k) => JSON.stringify(block.props[k]) === JSON.stringify(patch[k]))) return;
            this.pushHistory(`props:${id}:${Object.keys(patch).join(',')}`);
            block.props = { ...block.props, ...patch };
            this.touch();
        },

        updateTheme(patch) {
            this.pushHistory(`theme:${Object.keys(patch).join(',')}`);
            this.schema.theme = { ...this.schema.theme, ...patch };
            this.touch();
        },

        updateMeta(patch) {
            this.pushHistory(`meta:${Object.keys(patch).join(',')}`);
            this.schema.meta = { ...this.schema.meta, ...patch };
            this.touch();
        },

        reorder(newSections) {
            if (newSections.map((s) => s.id).join() === this.schema.sections.map((s) => s.id).join()) return; // nothing moved
            this.pushHistory();
            this.schema.sections = newSections;
            this.touch();
        },

        addBlock(block, index) {
            this.pushHistory();
            if (index !== undefined) {
                this.schema.sections.splice(index, 0, block);
            } else {
                this.schema.sections.push(block);
            }
            this.touch();
            this.selectedId = block.id;
        },

        updateSection(id, newSection) {
            const idx = this.schema.sections.findIndex(s => s.id === id);
            if (idx === -1) return false; // the section was removed while the AI worked
            this.pushHistory();
            this.schema.sections[idx] = { ...newSection, id };
            this.touch();
            return true;
        },

        duplicateBlock(id) {
            const idx = this.schema.sections.findIndex(s => s.id === id);
            if (idx === -1) return;
            const source = this.schema.sections[idx];
            const copy = JSON.parse(JSON.stringify(source));
            copy.id = `blk_${source.type}_${Date.now()}_${Math.random().toString(36).slice(2, 6)}`;
            this.addBlock(copy, idx + 1);
        },

        moveBlock(id, direction) {
            const idx = this.schema.sections.findIndex(s => s.id === id);
            const target = idx + direction;
            if (idx === -1 || target < 0 || target >= this.schema.sections.length) return;
            const sections = [...this.schema.sections];
            [sections[idx], sections[target]] = [sections[target], sections[idx]];
            this.reorder(sections);
        },

        removeBlock(id) {
            this.pushHistory();
            this.schema.sections = this.schema.sections.filter(s => s.id !== id);
            if (this.selectedId === id) {
                this.selectedId = null;
            }
            this.touch();
        },

        /**
         * Snapshot for undo. Consecutive edits to the same field (a word being typed, a colour
         * being dragged) collapse into one step, so undo goes back an edit, not a keystroke.
         */
        pushHistory(group = null) {
            if (!this.schema) return;
            const now = Date.now();
            if (group && group === this.lastGroup && now - this.lastGroupAt < GROUP_MS) {
                this.lastGroupAt = now;
                return;
            }
            this.lastGroup = group;
            this.lastGroupAt = now;
            this.history.push(snapshot(this.schema));
            this.future = [];
            if (this.history.length > 100) {
                this.history.shift();
            }
        },

        undo() {
            if (this.history.length) {
                this.future.push(snapshot(this.schema));
                this.schema = JSON.parse(JSON.stringify(this.history.pop()));
                this.lastGroup = null;
                this.touch();
            }
        },

        redo() {
            if (this.future.length) {
                this.history.push(snapshot(this.schema));
                this.schema = JSON.parse(JSON.stringify(this.future.pop()));
                this.lastGroup = null;
                this.touch();
            }
        },

        /** @param {number} [revision] the revision that was saved; later edits keep the page dirty */
        markClean(revision = this.revision) {
            if (revision === this.revision) {
                this.dirty = false;
            }
            this.saving = false;
        },
    },
});
