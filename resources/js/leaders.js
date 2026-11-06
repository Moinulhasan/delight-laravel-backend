import { setPreview, setupCrud } from './crud';

const removePhoto = document.getElementById('remove-photo-row');

setupCrud({
    noun: 'leader',
    where: 'website',
    files: ['photo_image'],
    checkboxes: ['remove_photo'],
    fill(form, leader) {
        for (const name of ['name', 'initials', 'role_en', 'role_bn', 'quote_en', 'quote_bn', 'photo_alt_en', 'photo_alt_bn']) {
            form.elements[name].value = leader?.[name] ?? '';
        }
        setPreview('photo_image', leader?.photo);
        removePhoto.hidden = !leader?.photo;
    },
});
