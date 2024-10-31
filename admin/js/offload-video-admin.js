jQuery(document).ready(function() {

    // Event handler for the delete all button
    jQuery(document).on('click', '.delete_all_button', function() {
        const selected_entry = [];
        jQuery('input[name="deleteSelected"]:checked').each(function() {
            selected_entry.push(this.value);
        });

        // Function to load external scripts dynamically
        const loadScript = (src, async = true, type = "text/javascript") => {
            return new Promise((resolve, reject) => {
                try {
                    const el = document.createElement("script");
                    const container = document.head || document.body;

                    el.type = type;
                    el.async = async;
                    el.src = src;

                    el.addEventListener("load", () => {
                        resolve({ status: true });
                    });

                    el.addEventListener("error", () => {
                        reject({
                            status: false,
                            message: `Failed to load the script ${src}`
                        });
                    });

                    container.appendChild(el);
                } catch (err) {
                    reject(err);
                }
            });
        };

        // Load SweetAlert2 script and handle delete action
        loadScript("//cdn.jsdelivr.net/npm/sweetalert2@11")
            .then((data) => {
                if (selected_entry.length == 0) {
                    Swal.fire('Select videos to delete!', '', 'info');
                } else {
                    Swal.fire({
                        title: 'Delete selected videos?',
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            var form = new FormData();
                            form.append('videos', selected_entry);
                            form.append('action', 'bulk_delete_video');
                            form.append('nonce', offloadvideos.nonce); // Add nonce

                            var ajaxurl = jQuery("#bsacft_admin_ajax_url").val();
                            jQuery.ajax({
                                type: "POST",
                                enctype: 'multipart/form-data',
                                url: offloadvideos.ajax_url,
                                data: form,
                                cache: false,
                                dataType: "text",
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    if (response == "OK") {
                                        Swal.fire('Deleted!', '', 'success');
                                        setTimeout(function() {
                                            location.reload();
                                        }, 3000);
                                    } else {
                                        Swal.fire(response.message, '', 'info');
                                        setTimeout(function() {
                                            location.reload();
                                        }, 5000);
                                    }
                                }
                            });
                        }
                    });
                }
            })
            .catch((err) => {
                console.error(err);
            });

        return false;
    });

    // Event handler for the API settings form submission
    jQuery("#bsacft_key_form").on('submit', function() {
        var form = new FormData();

        if (jQuery("#streaming_connect_service").val() == 'bunny') {
            form.append('BUNNY_ACCESS_KEY', jQuery("#BUNNY_ACCESS_KEY").val());
            form.append('BUNNY_LIBRARY_ID', jQuery("#BUNNY_LIBRARY_ID").val());
            if (isNaN(jQuery("#BUNNY_FILE_UPLOAD_LIMIT").val())) {
                alert("File upload limit is not a number.");
                return false;
            } else {
                form.append('BUNNY_FILE_UPLOAD_LIMIT', jQuery("#BUNNY_FILE_UPLOAD_LIMIT").val());
            }
        } else if (jQuery("#streaming_connect_service").val() == 'amazon') {
            form.append('amazon_s3_bucket', jQuery("#amazon_s3_bucket").val());
            form.append('amazon_s3_key', jQuery("#amazon_s3_key").val());
            form.append('amazon_s3_secret', jQuery("#amazon_s3_secret").val());
            form.append('amazon_s3_region', jQuery("#amazon_s3_region").val());
            if (isNaN(jQuery("#AMAZON_FILE_UPLOAD_LIMIT").val())) {
                alert("File upload limit is not a number.");
                return false;
            } else {
                form.append('AMAZON_FILE_UPLOAD_LIMIT', jQuery("#AMAZON_FILE_UPLOAD_LIMIT").val());
            }
        }

        form.append('streaming_connect_service', jQuery("#streaming_connect_service").val());
        form.append('action', 'verify_and_save_api_settings');
        form.append('nonce', offloadvideos.nonce); // Add nonce

        var ajaxurl = jQuery("#bsacft_admin_ajax_url").val();
        jQuery.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            url: offloadvideos.ajax_url,
            dataType: "text",
            data: form,
            processData: false,
            contentType: false,
            cache: false,
            success: function(response) {
                if (response == 'success') {
                    window.location.href = window.location.href + "&bsacft_success=success";
                } else if (response == 'failed') {
                    window.location.href = window.location.href + "&bsacft_success=failed";
                }
            }
        });

        return false;
    });

    // Event handler for streaming service selection change
    jQuery("#streaming_connect_service").on('change', function() {
        var streaming_connect_service = jQuery(this).val();
        jQuery(".settings_div").hide();
        jQuery("." + streaming_connect_service + "_settings_div").show();
    });
});

// Function to handle tab switching
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}
