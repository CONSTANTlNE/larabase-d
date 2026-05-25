document.addEventListener('htmx:afterOnLoad', function (event) {

    let htmlresponse = event.detail.xhr.response;

    let initiator = event.detail.elt;


    // const initiator = event.target;
    // console.log(event.detail)
    const xhr = event.detail.xhr;
    // console.log(xhr.status)


    if (xhr.status === 500) {
        let errorWindow = window.open("", "_blank", "width=1400,height=800");

        if (errorWindow) {
            errorWindow.document.open();
            errorWindow.document.write(htmlresponse);
            errorWindow.document.close();
        } else {
            console.error("Popup blocked or could not be opened.");
        }
    }

    if (xhr.status === 419) {
        window.location.reload();
    }


    if (xhr.status === 200) {

        if (initiator.id === 'test') {

            // document.getElementById("closerestaurantmodal").click()

        }
    }

});


