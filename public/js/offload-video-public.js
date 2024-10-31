jQuery(document).ready(function(){
        jQuery(document).on('click','.open_dropzone',function(){
         jQuery("div#streaming_connect_myModal").modal("show");
         jQuery(".modal-backdrop.fade.show").show();
         jQuery(".modal.fade.video_stream").addClass("show");
         jQuery("body").addClass("modal-open");
         return false;
         });

 jQuery(document).on('click','.video_stream button.close',function(){
   jQuery("body").removeClass("modal-open");
    jQuery(".modal.fade.video_stream.show").removeClass("show");
    jQuery(".modal-backdrop.fade.show").hide();
    
});

        jQuery(".cwebco_file_uploader").on('change',function(){
        var url = window.URL.createObjectURL(jQuery(".cwebco_file_uploader").prop('files')[0]);   
        jQuery(".video_result").html('<video width="100%" controls><source src="'+url+'" type="video/mp4"></video>');
        });

        jQuery(document).on('submit','#video_uploader_form',function(){
            var form_data = new FormData();
            var streaming_connect_service = jQuery(this).attr('streaming_connect_service');
            form_data.append('media_file', jQuery(".cwebco_file_uploader").prop('files')[0]);
            form_data.append('streaming_connect_service',streaming_connect_service);
            form_data.append('action', 'send_course_media_on_bunny');
            // Add the nonce for security
            form_data.append('security', offloadVideoPublic.nonce);

                jQuery.ajax({
                type: "POST",
                enctype: 'multipart/form-data',
                url: offloadVideoPublic.ajax_url,
                dataType: "json",
                data: form_data,
                processData: false,
                contentType: false,
                cache: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", progressHandler, false);
                    xhr.addEventListener("load", completeHandler, false);
                    xhr.addEventListener("error", errorHandler, false);
                    xhr.addEventListener("abort", abortHandler, false);
                    return xhr;
                },
                success: function(response) 
                {  
                if(streaming_connect_service=='bunny')
                {
                    if(response.video_id)
                    {
                        jQuery(".video_progress").html('<p class="upload_success">Congratulations! the video upload is complete. However, it may take approx. 30 mins to process the video depending on its size before making it available for use.</p>');
                        setTimeout(function(){
                            location.reload();
                        }, 5000);
                    } 
                    else
                    {
                        jQuery(".video_progress").html('<p class="upload_processing">Failed : '+response+'</p>');   
                    }
                }
                else if(streaming_connect_service=='amazon')
                {
                    if(response.ObjectURL)
                    {
                        jQuery(".video_progress").html('<p class="upload_success">Congratulations! the video upload is complete. However, it may take approx. 30 mins to process the video depending on its size before making it available for use.</p>');
                        setTimeout(function(){
                            location.reload();
                        }, 5000);   
                    }
                    else
                    {
                        jQuery(".video_progress").html('<p class="upload_processing">Failed : '+response+'</p>');
                    }
                }   
                },
                error: function (jqXHR, exception) 
                {
                    var cleanTextresponse = jqXHR.responseText.replace(/<\/?[^>]+(>|$)/g, "");
                    jQuery(".video_progress").html('<p class="upload_processing">Failed : '+cleanTextresponse+'</p>');
                },
            });
            return false;
        });

    jQuery(document).on('click','.copy_embed',function(){
        var video_id = jQuery(this).attr('guid');
        var copyText = jQuery("."+video_id);
        var org_element = jQuery(this);
        copyText.select();
        navigator.clipboard.writeText(copyText.val()); 
        jQuery(this).css('color','mediumseagreen');
        setTimeout(function()
        {
            org_element.css('color','#fe816b');   
        }, 3000);    
    });	
    
    jQuery(document).on('click','.delete_video',function($){
        var video_id = jQuery(this).attr('guid');
        const loadScript = (src, async = true, type = "text/javascript") => {
            return new Promise((resolve, reject) => {
                try {
                    const el = document.createElement("script");
                    const container = document.head || document.body;

                    el.type = type;
                    el.async = async;
                    el.src = src;

                    el.addEventListener("load", () => {
                        //console.log(container);
                        resolve({
                            status: true
                        });
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

               loadScript("//cdn.jsdelivr.net/npm/sweetalert2@11")
                .then((data) => {
                    Swal.fire({
                    title: 'Do you want to delete this video?',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    }).then((result) => {
                    if(result.isConfirmed) {
                    var form = new FormData();
                    form.append('video_id', video_id);
                    form.append('action', 'delete_video_on_bunny');
                    // Add the nonce for security
                    form.append('security', offloadVideoPublic.nonce);

                    var ajaxurl = jQuery("#bsacft_ajax_url").val();
                    jQuery.ajax({
                    type: "POST",
                    enctype: 'multipart/form-data',
                    url: offloadVideoPublic.ajax_url,
                    dataType: "text",
                    data: form,
                    processData: false,
                    contentType: false,
                    cache: false,
                    success: function(response) {
                        if(response=="OK")
                        {
                        Swal.fire('Deleted!', '', 'success').then((result) => {location.reload();});
                        }
                        else
                        {
                        Swal.fire(response.message, '', 'info').then((result) => {location.reload();});
                        }
                    }
                    });                    
                    }
                    })
                })
                .catch((err) => {
                    console.error(err);
                });   
    }); 

});

'use strict'

let upload          = null
let uploadIsRunning = false
const toggleBtn       = document.querySelector('.toggle-btn')
const input           = document.querySelector('input[name=cwebco_file_uploader]')
const progress        = document.querySelector('.progress')
const progressBar     = progress.querySelector('.bar')
const alertBox        = document.querySelector('#support-alert')
const uploadList      = document.querySelector('#upload-list')
const chunkInput      = document.querySelector('#chunksize')
const parallelInput   = document.querySelector('#paralleluploads')
const endpointInput   = document.querySelector('#endpoint')

function reset () {
  input.value = ''
  //toggleBtn.textContent = 'Start Upload'
  upload = null
  uploadIsRunning = false
}

function askToResumeUpload (previousUploads, currentUpload) {
  if (previousUploads.length === 0) return

  let text = 'You tried to upload this file previously at these times:\n\n'
  previousUploads.forEach((previousUpload, index) => {
    text += `[${index}] ${previousUpload.creationTime}\n`
  })
  text += '\nEnter the corresponding number to resume an upload or press Cancel to start a new upload'

  const answer = prompt(text)
  const index = parseInt(answer, 10)

  if (!Number.isNaN(index) && previousUploads[index]) {
    currentUpload.resumeFromPreviousUpload(previousUploads[index])
  }
}

  function startBunnyUpload (items, index) 
  {
  const file = items[index]
  console.log(items);
  if (!file) 
  {
    return
  }
  //if(file.size > 2147483648)
  var allowed_size_gb = parseFloat(jQuery('#BUNNY_FILE_UPLOAD_LIMIT').val());
  if(isNaN(allowed_size_gb))
  {
  allowed_size_gb = 5;
  }
  var allowed_size_bytes = allowed_size_gb*1024*1024*1024;
  if(file.size > allowed_size_bytes)
  {
    //jQuery(".video_progress").html('<p class="upload_processing">Warning : Can not upload files larger then 2GB.</p>');
    jQuery('.progress.progress-striped.progress-success .item_'+index).css('height','43px!important');
    jQuery('.progress.progress-striped.progress-success .item_'+index).html('<div class="top_section"><div class="file_name"><p class="file_name_text">'+file.name+'</p></div><p class="progress_percentage progress_'+file.size+'" title="Warning : Can not upload files larger then '+allowed_size_gb+'GB.">Failed <span class="failed_icon">&#x26A0;</span></p></div>');
    jQuery(".progress_"+file.size).css('color','tomato');
    startBunnyUpload (items, index+1);
    return false;
  }
  jQuery(".video_progress").html('');
  const endpoint = endpointInput.value;
  let chunkSize = parseInt(chunkInput.value, 10)
  if (Number.isNaN(chunkSize)) {
    chunkSize = Infinity
  }

  let parallelUploads = parseInt(parallelInput.value, 10)
  if (Number.isNaN(parallelUploads)) {
    parallelUploads = 1
  }

    //toggleBtn.textContent = 'Pause upload';
    var form_data = new FormData();
    form_data.append('streaming_connect_service','bunny');
    form_data.append('media_file', file.name);
    form_data.append('action', 'send_course_media_on_bunny');
    // Add the nonce for security
    form_data.append('security', offloadVideoPublic.nonce);
    jQuery.ajax({
    type: "POST",
    enctype: 'multipart/form-data',
    url: offloadVideoPublic.ajax_url,
    dataType: "json",
    data: form_data,
    processData: false,
    contentType: false,
    cache: false,
    success: function(response) 
    {   
      if(response.video_id)
      { 
        //jQuery('.progress.progress-striped.progress-success').append('<li class="item_'+index+'"><p class="file_name">'+file.name+'</p><p class="progress_percentage progress_'+file.size+'"></p><p class="bar" id="'+file.size+'" style="width: 0%;"></p></li>');
        var library_id = response.library_id;  
        var video_guid = response.video_id;
        var collection_id = response.access_key;
        var now = new Date();
       var time = now.getTime();
       var expiration_time = time + 1000*36000;
        var auth_signature = CryptoJS.SHA256(library_id+collection_id+expiration_time+video_guid);  
        var options_new = {
    endpoint,
    chunkSize,
    retryDelays: [0, 1000, 3000, 5000],
    parallelUploads,
    headers: {
        AuthorizationSignature: auth_signature, // SHA256 signature (library_id + api_key + expiration_time + video_id)
        AuthorizationExpire: expiration_time, // Expiration time as in the signature,
        VideoId: video_guid, // The guid of a previously created video object through the Create Video API call
        LibraryId: library_id,
    },
    metadata   : {
      title: file.name,
      filetype: file.type,
    },
    onError (error) {
      if (error.originalRequest) {
        if (window.confirm(`Failed because: ${error}\nDo you want to retry?`)) {
          upload.start()
          uploadIsRunning = true
          return
        }
      } else {
        window.alert(`Failed because: ${error}`)
      }

      reset()
    },
    onProgress (bytesUploaded, bytesTotal) {
      jQuery("li.item_"+index+" .toggle-btn").show();
      const percentage = ((bytesUploaded / bytesTotal) * 100).toFixed(2)
      jQuery(".progress.progress-striped.progress-success li.item_"+index+" .bar").css('width', percentage+'%');
      jQuery(".progress_"+file.size).text(`${percentage}%`);
      //console.log(bytesUploaded, bytesTotal, `${percentage}%`)
    },
    onSuccess () {
      jQuery("li.item_"+index+" .toggle-btn").hide();
      jQuery(".progress_"+file.size).css('color','green');
      jQuery(".progress_"+file.size).html('&#10004;');
      var last_index = items.length-1;
      console.log(index+'_'+last_index);
      if(index==last_index)
      {
      jQuery(".video_progress").html('<p class="upload_success '+index+'_'+last_index+'">Congratulations! the video upload is complete. However, it may take approx. 30 mins to process the video depending on its size before making it available for use.</p>');
      setTimeout(function(){
      location.reload();
      }, 5000);
      }
      else
      {
        startBunnyUpload (items, index+1);
      }
    },
  }
        upload = new tus.Upload(file, options_new)
        //askToResumeUpload(previousUploads, upload)
        upload.start()
        uploadIsRunning = true
      } 
      else
      {
        jQuery(".video_progress").html('<p class="upload_processing">Failed : '+response+'</p>');   
      }  
    }
    });
}


//amazon file upload


function startAmazonUpload (items, index) 
  {
  const file = items[index]
  console.log(items);
  if (!file) 
  {
    return
  }
  //if(file.size > 2147483648)
  var allowed_size_gb = parseFloat(jQuery('#AMAZON_FILE_UPLOAD_LIMIT').val());
  if(isNaN(allowed_size_gb))
  {
  allowed_size_gb = 5;
  }
  var allowed_size_bytes = allowed_size_gb*1024*1024*1024;
  if(file.size > allowed_size_bytes)
  {
    //jQuery(".video_progress").html('<p class="upload_processing">Warning : Can not upload files larger then 2GB.</p>');
    jQuery('.progress.progress-striped.progress-success .item_'+index).css('height','43px!important');
    jQuery('.progress.progress-striped.progress-success .item_'+index).html('<div class="top_section"><div class="file_name"><p class="file_name_text">'+file.name+'</p></div><p class="progress_percentage progress_'+file.size+'" title="Warning : Can not upload files larger then '+allowed_size_gb+'GB.">Failed <span class="failed_icon">&#x26A0;</span></p></div>');
    jQuery(".progress_"+file.size).css('color','tomato');
    startAmazonUpload (items, index+1);
    return false;
  }
  jQuery(".video_progress").html('');

    //toggleBtn.textContent = 'Pause upload';
    var form_data = new FormData();
    form_data.append('streaming_connect_service','amazon');
    form_data.append('media_file', file);
    form_data.append('action', 'send_course_media_on_bunny');
    // Add the nonce for security
    form_data.append('security', offloadVideoPublic.nonce);
    
    jQuery.ajax({
    type: "POST",
    enctype: 'multipart/form-data',
    url: offloadVideoPublic.ajax_url,
    dataType: "json",
    data: form_data,
    processData: false,
    contentType: false,
    cache: false,
    xhr: function() 
    {
      var xhr = new window.XMLHttpRequest();
      xhr.upload.addEventListener("progress", function progressHandler(event) {
        jQuery(".progress_"+file.size).css('color','green');
        const percentage = (event.loaded / event.total) * 100;
        console.log(percentage);
        jQuery(".progress.progress-striped.progress-success li.item_"+index+" .bar").css('width', Math.round(percentage)+'%');
        jQuery(".progress_"+file.size).text(`${Math.round(percentage)}%`);
      }, false);

      xhr.addEventListener("load", function completeHandler(event) {
          jQuery(".progress_"+file.size).html('&#10003;');
          var last_index = items.length-1;
          console.log(index+'_'+last_index);
          if(index==last_index)
          {
            jQuery(".video_progress").html('<p class="upload_processing">Wait while we are processing your video!</p>');
          }
          else
          {
            startAmazonUpload (items, index+1);
          }
      }, false);

      xhr.addEventListener("error", errorHandler, false);
      xhr.addEventListener("abort", abortHandler, false);
      return xhr;
    },
    success: function(response) 
    {   
      if(response.ObjectURL)
      {
        var last_index = items.length-1;
        console.log(index+'_'+last_index);
        if(index==last_index)
        {
          jQuery(".video_progress").html('<p class="upload_success '+index+'_'+last_index+'">Congratulations! the video upload is complete. However, it may take approx. 30 mins to process the video depending on its size before making it available for use.</p>');
          setTimeout(function(){
          location.reload();
          }, 5000);
        }
        
      } 
      else
      {
        jQuery(".video_progress").html('<p class="upload_processing">Failed : '+response+'</p>');   
      }  
    },
    error: function (jqXHR, exception) 
    {
      var cleanTextresponse = jqXHR.responseText.replace(/<\/?[^>]+(>|$)/g, "");
      jQuery(".video_progress").html('<p class="upload_processing">Failed : '+cleanTextresponse+'</p>');
    },
    });
}


//amazon file upload


jQuery(document).on('click','.toggle-btn', function(e) {
  e.preventDefault()
  if (upload) {
    if (uploadIsRunning) {
      upload.abort()
      jQuery(this).html('<i class="fa fa-play" title="Resume Video" aria-hidden="true"></i>');
      uploadIsRunning = false
    } else {
      upload.start()
      jQuery(this).html('<i class="fa fa-pause" title="Pause Video" aria-hidden="true"></i>');
      uploadIsRunning = true
    }
  }
});


// toggleBtn.addEventListener('click', (e) => {
//   e.preventDefault()

//   if (upload) {
//     if (uploadIsRunning) {
//       upload.abort()
//       toggleBtn.textContent = 'Resume Upload'
//       uploadIsRunning = false
//     } else {
//       upload.start()
//       toggleBtn.textContent = 'Pause Upload'
//       uploadIsRunning = true
//     }
//   } //else if (input.files.length > 0) {
//   //   startUpload()
//   // } else {
//   //   input.click()
//   // }
// })


//input.addEventListener('change', startUpload);





  Dropzone.autoDiscover = false;

  var dzoptions = {
  paramName: "cwebco_file_uploader",
  maxFilesize: 2000,
  url: 'https://video.bunnycdn.com/tusupload/',
  previewsContainer: "#dropzone-previews",
  uploadMultiple: true,
  acceptedFiles: "video/*",
  autoProcessQueue: true,
  parallelUploads: 1,
  maxFiles: 20,
  init: function() {
    var cd;
    const files = []; 
    this.on("addedfile", function(file) 
    {
      var _this = this;
      jQuery('.dz-size').hide();
      jQuery('.dz-error-mark').hide();
     jQuery('.dz-error-message').hide();
      jQuery('.dz-details img').hide();
      files.push(file);
      //startUpload(file);
    });
    this.on("complete", async function (file) {
      console.log('john');
      if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
        jQuery(".progress.progress-striped.progress-success").show();
        if(jQuery('#streaming_connect_service').val()=='bunny')
        {
          files.forEach(function(currentFile, index, arr){
            jQuery('.progress.progress-striped.progress-success').append('<li class="item_'+index+'"><div class="top_section"><div class="file_name"><button class="btn stop toggle-btn"><i class="fa fa-pause" title="Pause Video" aria-hidden="true"></i></button><p class="file_name_text">'+currentFile.name+'</p></div><p class="progress_percentage progress_'+currentFile.size+'">0% waiting</p></div><p class="bar" id="'+currentFile.size+'" style="width: 0%;"></p></li>');
          });
          startBunnyUpload (files, 0);
        }
        else if(jQuery('#streaming_connect_service').val()=='amazon') 
        {
          files.forEach(function(currentFile, index, arr){
            jQuery('.progress.progress-striped.progress-success').append('<li class="item_'+index+'"><div class="top_section"><div class="file_name"><p class="file_name_text">'+currentFile.name+'</p></div><p class="progress_percentage progress_'+currentFile.size+'">0% waiting</p></div><p class="bar" id="'+currentFile.size+'" style="width: 0%;"></p></li>');
          });
          startAmazonUpload (files, 0);
        }
      }
    });
  }
};


var myDropzone2 = new Dropzone("div#myId", dzoptions);



function _(el) {
  return document.getElementById(el);
}

function errorHandler(event) {
  _("status").innerHTML = "Upload Failed";
}

function abortHandler(event) {
  _("status").innerHTML = "Upload Aborted";
}
