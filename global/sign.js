(function ($) {
    'use strict';

    $(function () {
        // *********************ToMarcus**************************
        // ************* Check wallet connection (In this test produc, should test user_id:3)********
        // *******************************************************
        axios.post('custom.php', { type: 'GetUserInfo' })
            .then(response => {
                let json = response.data;
                if (json && json.xumm_address) {
                    console.log('*************When page loading, GetUnserInfo Response=', json);
                    $(".auth").text(json.xumm_address);
                    accountAddress = json.xumm_address;
                }
                else {
                    console.log('*************When page loading, since no user connectd, change button label with ...');
                    $(".auth").text('Connect Wallet');
                    // xumm.logout();
                }
            })
            .catch(error => {
                console.log(error);
            });
    })


    //Login and Logout Button
    $('#btn_login').on('click', function () {
        handleLogin();
    });


    $('.btn_logout').on('click', function () {
        handleLogout();
    });

    //When click confirm button on modal dialog on List Item modal
    $('.btn_login_modal').on('click', function () {

        $("#loginModal").toggleClass('active');
    });


    // *********************ToMarcus**************************
    // ************* Connect Wallet on desktop****************
    // *******************************************************
    $('.auth').on('click', async () => {
        axios.post('custom.php', { type: 'GetUserInfo' })
            .then(response => {
                let json = response.data;
                if (json) {
                    xumm.logout();
                    console.log('*************With clicking button, GetUserInfo Response=', json); // handle the response

                    axios.post('custom.php', { type: 'RemoveUserInfo' })
                        .then(response => {
                            if (response.data) {
                                console.log('*************RemoveUnserInfo Response=', response.data); // handle the response
                                if (response.data.indexOf("success") >= 0) {
                                    $(".auth").text("Connect Wallet");
                                    alert("You have successfully xumm signed out!");
                                }
                            }
                            else {
                                console.log(e);
                            }
                        })
                        .catch(error => {
                            console.error(error);
                        })
                }
                else {
                    xumm.logout();
                    console.log('*************With clicking button, since no user connected, go to sign...'); // handle the response
                    signIn();
                }
            })
            .catch(error => {
                console.error(error);
            })

    });

    // *********************ToMarcus****************************
    // ************* Xumm wallet connection callack on desktop**
    // *********************************************************

    xumm.on("success", async () => {
        const state = await xumm.state(); // state.sdk = instance of https://www.npmjs.com/package/xumm-sdk

        console.log("*************Connected account address:", state?.me?.sub);

        state?.sdk?.ping().then(async (pong) => {
            const payload = await state.sdk.payload.get(pong.jwtData.payload_uuidv4);
            console.log("*************Wallet connect payload:", payload);

            axios.post('custom.php', { type: 'UpdateUserInfo', payload })
                .then(response => {
                    console.log(response);
                    if (response.data) {
                        console.log('*************UpdateUserInfo Response:', response.data);
                        if (response.data.indexOf('success') >= 0) {
                            $(".auth").text(state?.me?.sub);
                            alert('You have successfully xumm signed in!');
                            // loadNftInfos().then(() => {
                            //     location.reload();
                            //   });
                            location.reload();
                        } else if (response.data.indexOf('user_mismatch') >= 0) {
                            alert('In this test product, you should login with Test User');
                            xumm.logout();
                        } else if (response.data.indexOf('user_exist') >= 0) {
                            console.log('*************User already exist!');
                        }

                    } else {
                        console.log('*************UpdateUserInfo No Response:');
                    }
                })
                .catch(error => {
                    console.error(error);
                });
        });

    });

    // *********************ToMarcus**************************
    // ************* Xumm Connect Success Callback on Mobile**
    // *******************************************************
    xumm.on("retrieved", async () => {
        const state = await xumm.state(); // state.sdk = instance of https://www.npmjs.com/package/xumm-sdk

        console.log("Connected account address from mobile phone:", state?.me?.sub);

        state?.sdk?.ping().then(async (pong) => {
            const payload = await state.sdk.payload.get(pong.jwtData.payload_uuidv4);
            console.log("*************Wallet connect payload:", payload);

            axios.post('custom.php', { type: 'UpdateUserInfo', payload })
                .then(response => {
                    console.log(response);
                    if (response.data) {
                        console.log('*************UpdateUserInfo Response:', response.data);
                        if (response.data.indexOf('success') >= 0) {
                            $(".auth").text(state?.me?.sub);
                            alert('You have successfully logged in!');
                        } else if (response.data.indexOf('user_mismatch') >= 0) {
                            alert('In this test product, you should login with Test User');
                            xumm.logout();
                        } else if (response.data.indexOf('user_exist') >= 0) {
                            console.log('*************User already exist!');
                        }

                    } else {
                        console.log('*************UpdateUserInfo No Response:');
                    }
                })
                .catch(error => {
                    console.error(error);
                });
        });
    });

    // *********************ToMarcus******************************
    // *************************Handle LoginIn********************
    // ***********************************************************
    async function handleLogin() {
        if (confirm("Are you sure want to login?")) {
            var user_id = $('#user_name').val();
            var user_password = $('#user_password').val();

            console.log("*************handleLogin ", user_id, user_password);
            if(!user_id || !user_password){
                alert("Please input user or password!");
            }

            LoginUser(user_id, user_password);
        }
    }

    async function handleLogout() {
        if (confirm("Are you sure want to logout?")) {
            console.log("*************handleLogout ");
            LogoutUser();
        }
    }
})(jQuery); // End of use strict