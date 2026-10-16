import { ref } from 'vue';
import axios from 'axios';

// The project's favicon set (stored on the project, not in the page schema).
const favicon = ref(null);
const busy = ref(false);

export function useFavicon() {
    function set(value) {
        favicon.value = value || null;
    }

    /** Build the favicon from one of the project's images. */
    async function fromImage(projectId, url, followsLogo = true) {
        busy.value = true;
        try {
            const { data } = await axios.post(route('projects.favicon', projectId), { url, followsLogo });
            favicon.value = data.favicon;
            return data.favicon;
        } finally {
            busy.value = false;
        }
    }

    async function reset(projectId) {
        busy.value = true;
        try {
            await axios.delete(route('projects.favicon.destroy', projectId));
            favicon.value = null;
        } finally {
            busy.value = false;
        }
    }

    return { favicon, busy, set, fromImage, reset };
}
