import { setPreview, setupCrud } from './crud';

const removeMobile = document.getElementById('remove-mobile-row');

setupCrud({
    noun: 'slide',
    where: 'home page',
    files: ['desktop_image', 'mobile_image'],
    checkboxes: ['remove_mobile'],
    fill(form, slide) {
        form.elements.alt_en.value = slide?.alt_en ?? '';
        form.elements.alt_bn.value = slide?.alt_bn ?? '';
        setPreview('desktop_image', slide?.desktop);
        setPreview('mobile_image', slide?.mobile);
        removeMobile.hidden = !slide?.mobile;
    },
});
