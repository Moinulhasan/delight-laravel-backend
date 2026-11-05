import { setPreview, setupCrud } from './crud';

const removePoster = document.getElementById('remove-poster-row');
const linkInput = document.getElementById('youtube_link');
const thumbnail = document.getElementById('youtube-preview');

// Only for the preview: the server has the final say on what counts as a YouTube link.
function youtubeId(value) {
    const text = value.trim();

    return /^[\w-]{11}$/.test(text) ? text : (text.match(/(?:youtu\.be\/|[?&]v=|\/(?:embed|shorts|live|v)\/)([\w-]{11})(?:[?&/#]|$)/)?.[1] ?? null);
}

function showThumbnail() {
    const id = youtubeId(linkInput.value);
    thumbnail.src = id ? `https://i.ytimg.com/vi/${id}/hqdefault.jpg` : '';
    thumbnail.hidden = !id;
}

linkInput.addEventListener('input', showThumbnail);

setupCrud({
    noun: 'video',
    where: 'home page',
    files: ['poster_image'],
    checkboxes: ['remove_poster'],
    fill(form, video) {
        for (const name of ['title_en', 'title_bn', 'description_en', 'description_bn', 'upload_date', 'duration']) {
            form.elements[name].value = video?.[name] ?? '';
        }
        linkInput.value = video ? `https://youtu.be/${video.youtube_id}` : '';
        showThumbnail();
        setPreview('poster_image', video?.poster);
        removePoster.hidden = !video?.poster;
    },
});
