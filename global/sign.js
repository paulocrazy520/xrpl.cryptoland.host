$(document).ready(async function () {
    // *********************ToMarcus**************************
    // ************* Check wallet connection (In this test produc, should test user_id:3)********
    // *******************************************************

    axios.post('custom.php', { type: 'GetUserInfo' })
        .then(response => {
            let json = response.data;
            if (json && json.xumm_address) {
                console.log('*************When page loading, GetUnserInfo Response=', json);
                document.getElementById("auth").innerText = json.xumm_address;
                accountAddress =  json.xumm_address;
            }
            else {
                console.log('*************When page loading, since no user connectd, change button label with ...');
                document.getElementById("auth").innerText = 'Connect Wallet';
               // xumm.logout();
            }
        })
        .catch(error => {
            console.log(error);
        });

});


// *********************ToMarcus**************************
// ************* Connect Wallet on desktop****************
// *******************************************************

document.getElementById("auth").onclick = async () => {
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
                                document.getElementById("auth").innerText = "Connect Wallet";
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

};

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
                        document.getElementById('auth').innerText = state?.me?.sub;
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
                        document.getElementById('auth').innerText = state?.me?.sub;
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


