import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useEditor } from './editor.js';

const schema = () => ({
    version: 1, meta: { title: 'T' }, theme: { primaryColor: '#000' },
    sections: [
        { id: 'a', type: 'hero', props: { heading: 'Hi' } },
        { id: 'b', type: 'about', props: { heading: 'About', body: 'x' } },
    ],
});

describe('editor store', () => {
    let editor;
    beforeEach(() => {
        setActivePinia(createPinia());
        editor = useEditor();
        editor.load(schema());
        vi.useRealTimers();
    });

    it('bumps the revision on every change and only marks clean for the saved revision', () => {
        expect(editor.revision).toBe(0);
        editor.updateProps('a', { heading: 'One' });
        const saved = editor.revision;           // a save starts here…
        editor.updateProps('a', { heading: 'Two' }); // …and the user keeps typing
        editor.markClean(saved);
        expect(editor.dirty).toBe(true);         // the newer edit is still unsaved
        editor.markClean(editor.revision);
        expect(editor.dirty).toBe(false);
    });

    it('ignores a no-op update (contenteditable blur with nothing changed)', () => {
        editor.markClean();
        editor.updateProps('a', { heading: 'Hi' });
        expect(editor.dirty).toBe(false);
        expect(editor.history).toHaveLength(0);
    });

    it('groups keystrokes on the same field into one undo step', () => {
        vi.useFakeTimers();
        for (const c of ['H', 'He', 'Hel', 'Hell', 'Hello']) {
            editor.updateProps('a', { heading: c });
            vi.advanceTimersByTime(100);
        }
        expect(editor.history).toHaveLength(1);
        editor.undo();
        expect(editor.schema.sections[0].props.heading).toBe('Hi');

        // A pause, or a different field, starts a new step.
        editor.updateProps('a', { heading: 'A' });
        vi.advanceTimersByTime(1000);
        editor.updateProps('a', { heading: 'AB' });
        editor.updateProps('b', { heading: 'Z' });
        expect(editor.history).toHaveLength(3);
    });

    it('keeps undo snapshots detached from live state', () => {
        editor.updateProps('a', { heading: 'One' });
        editor.schema.sections[0].props.heading = 'mutated';
        editor.undo();
        expect(editor.schema.sections[0].props.heading).toBe('Hi');
        editor.redo();
        expect(editor.schema.sections[0].props.heading).toBe('mutated');
    });

    it('refuses to replace a section that no longer exists and keeps the id', () => {
        expect(editor.updateSection('zzz', { type: 'hero', props: {} })).toBe(false);
        expect(editor.updateSection('b', { id: 'other', type: 'about', props: { heading: 'New' } })).toBe(true);
        expect(editor.schema.sections[1]).toMatchObject({ id: 'b', props: { heading: 'New' } });
    });

    it('treats an unchanged reorder as no edit', () => {
        editor.markClean();
        editor.reorder([...editor.schema.sections]);
        expect(editor.dirty).toBe(false);
        editor.reorder([editor.schema.sections[1], editor.schema.sections[0]]);
        expect(editor.dirty).toBe(true);
        expect(editor.schema.sections[0].id).toBe('b');
    });
});
