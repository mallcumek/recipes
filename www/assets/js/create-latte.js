
    // Form file upload required
    document.getElementById("frm-postForm-image").required = true;
    // Preview an image before it is uploaded
    document.getElementById('frm-postForm-image').onchange = evt => {
    const [file] = evt.target.files;
    if (file) {
    document.getElementById('image-preview').src = URL.createObjectURL(file);
}
};
