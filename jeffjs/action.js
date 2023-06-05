(function ($) {
    'use strict';
    $(function () {
        $('.cs-preloader').delay(150).fadeOut('slow');
        // *********************ToMarcus**************************
        // ************* Check wallet connection (In this test produc, should test user_id:3)********
        // *******************************************************
        axios.post('jeffajax.php', { type: 'GetUserInfo' })
            .then(response => {
                let json = response.data;
                if (json && json.xumm_address) {
                    console.log('*************When page loading, GetUnserInfo Response=', json);
                    $(".auth").text(json.xumm_address);
                    accountAddress = json.xumm_address;
                }
                else {
                    console.log('*************When page loading, since no user connectd, change button label with ...');
                    $(".auth").text('Sign in Xumm');
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
    // ************* Sign in Xumm on desktop****************
    // *******************************************************
    $('.auth').on('click', async () => {
        axios.post('jeffajax.php', { type: 'GetUserInfo' })
            .then(response => {
                let json = response.data;
                if (json) {
                    if (!confirm("Are you sure want to sign out from xumm"))
                        return;
                        
                    xumm.logout();
                    console.log('*************With clicking button, GetUserInfo Response=', json); // handle the response

                    axios.post('jeffajax.php', { type: 'RemoveUserInfo' })
                        .then(response => {
                            if (response.data) {
                                console.log('*************RemoveUnserInfo Response=', response.data); // handle the response
                                if (response.data.indexOf("success") >= 0) {
                                    $(".auth").text("Sign in Xumm");
                                    alert("You have successfully xumm signed out!");
                                }
                            }
                            else {
                                console.log("**************RemoveUnserInfo failed");
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

            axios.post('jeffajax.php', { type: 'UpdateUserInfo', payload })
                .then(response => {
                    console.log(response);
                    if (response.data) {
                        console.log('*************UpdateUserInfo Response:', response.data);
                        if (response.data.indexOf('success') >= 0) {
                            $(".auth").text(state?.me?.sub);
                            alert('You have successfully xumm signed in!');
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

            axios.post('jeffajax.php', { type: 'UpdateUserInfo', payload })
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

    //When click confirm button on modal dialog on List Item modal
    $('#btn-list-item').on('click', function () {
        handleListItemClick();
    });

    //When click confirm button on modal dialog on Place BId modal
    $('#btn-place-item').on('click', function () {
        handlePlaceItemClick();
    });

    //When click Cancel Listing button on the main explorer page
    $('[data-modal]').on('click', function () {
        var modalId = $(this).attr('data-modal');

        switch (modalId) {
            case "#cancelList": //When click List Item button
                var nftId = $(this).attr('nft-id');
                console.log("***********CancelList selected by nft token:***********", modalId, nftId);
                handleCancelListingClick(nftId);
                break;
            case "#cancelBid": //When click List Item button
                var nftId = $(this).attr('nft-id');
                console.log("***********CancelBid selected by nft token:***********", modalId, nftId);
                handleCancelBidClick(nftId);
                break;
            case "#buyItem": //When click Buy Item button
                var nftId = $(this).attr('nft-id');
                var offerId = $(this).attr('offer-id');
                console.log("***********BuyItem selected by nft token:***********", modalId, nftId);
                handleBuyItemClick(nftId, offerId);
                break;
            case "#revealItem": //When click Buy Item button
                var nftId = $(this).attr('nft-id');
                console.log("***********RevealItem selected by nft token:***********", modalId, nftId);
                handleRevealItemClick(nftId);
                break;
            case "#claimItem": //When click Buy Item button
                var nftId = $(this).attr('nft-id');
                var offerId = $(this).attr('offer-id');
                console.log("***********ClaimItem selected by nft token:***********", modalId, nftId, offerId);
                handleClaimItemClick(nftId, offerId);
                break;
            default:
                break;
        }

    })

    // *********************ToMarcus******************************
    // *************************Handle LoginIn********************
    // ***********************************************************
    async function handleLogin() {
        if (confirm("Are you sure want to login?")) {
            var user_id = $('#user_name').val();
            var user_password = $('#user_password').val();

            console.log("*************handleLogin ", user_id, user_password);
            if (!user_id || !user_password) {
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

    // *********************ToMarcus**************************
    // ***********Handle List Item(Create Sell Offer)********
    // *******************************************************
    async function handleRevealItemClick(nftId) {

        if (confirm("Are you sure want to reveal this nft?")) {
            console.log("*************hhandleRevealItemClick ", nftId);

            $('.cs-preloader').delay(10).fadeIn('slow'); //Show loading screen

            axios.post('jeffajax.php', {
                type: "RevealItem",
                nftId: nftId
            })
                .then(response => {
                    console.log('*********************handleRevealItemClick Response=', response);
                    $('.cs-preloader').delay(10).fadeOut('slow'); //Show loading screen

                    if(response.data == true){
                        $('.cs-isotop_item[nft-id="' + nftId + '"]').removeClass('unrevealed').addClass('revealed');

                        if($('#unrevealedCount').val() > 0)
                        {
                            $('#unrevealedCount').val(parseInt($('#unrevealedCount').val())-1);
                            $('#revealedCount').val(parseInt($('#revealedCount').val())+1);
                        }

                        $('.cs-isotop').isotope('reloadItems').isotope('layout');

                        setTimeout(function () {
                            isotopInit();
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error(error);
                    $('.cs-preloader').delay(10).fadeOut('slow'); //Show loading screen
                });

        }
    }

    // *********************ToMarcus**************************
    // ***********Handle List Item(Create Sell Offer)********
    // *******************************************************
    async function handleClaimItemClick(nftId, offerId) {

        if (confirm("Are you sure want to claim this nft?")) {
            console.log("*************handleClaimItemClick ", nftId, offerId);
            await acceptSellOffer(accountAddress, nftId, offerId, "claim_offers");
            //await createSellOffer(accountAddress, nftId, amount);
        }
    }


    // *********************ToMarcus**************************
    // ***********Handle List Item(Create Sell Offer)********
    // *******************************************************
    async function handleListItemClick() {
        var modalId = '#listItem';
        var nftId = $(modalId).find('#nft_id').text();
        var amount = $(modalId).find('#list_bid_quantity').val();

        if (!amount || isNaN(amount)) {
            alert("Please valid amount");
            return;
        }
        if (confirm("Are you sure want to sell this item with these price?")) {
            console.log("*************handleListItemClick : Create Sell Offer", accountAddress, nftId, amount);
            await createSellOffer(accountAddress, nftId, amount);
        }
    }

    // *********************ToMarcus**************************
    // ***********Handle Place Item(Create Buy Offer)********
    // *******************************************************
    async function handlePlaceItemClick() {
        var modalId = '#placeBid';
        var nftId = $(modalId).find('#nft_id').text();
        var amount = $(modalId).find('#place_bid_quantity').val();
        var expire = $(modalId).find('#bid_expire').val();
        var owner = $(modalId).find('#owner').text();
        if (!amount || isNaN(amount)) {
            alert("Please valid amount");
            return;
        }

        if (confirm("Are you sure want to buy this item with these price and expire time?")) {
            console.log("*************handlePlaceBidClick : Create Buy Offer", accountAddress, nftId, amount, expire);
            await createBuyOffer(owner, accountAddress, nftId, amount, expire);
        }
    }



    // ***********Handle Buy Item(Accetp Sell Offer)*************
    // ***********************************************************
    async function handleBuyItemClick(nftId, offerId) {
        //get offer string from nftId

        if (offerId) {
            let response = await axios.get(
                `https://test-api.xrpldata.com/api/v1/xls20-nfts/offer/id/${offerId}`);
            let offer = response.data.data.offer;

            console.log("***********handleBuyItemClick: OfferId", offer);

            if (!offer) {
                alert("No buy offer:" + offerId);
                return;
            }


            let amount = parseInt(offer.Amount) / (10 ** 6);

            let alertString = "Are you sure want to accept this item from " + offer.Owner + " with " + amount + "Xrp?";
            if (confirm(alertString)) {
                console.log("*************handleBuyItemClick : Accept Buy Offer", accountAddress, nftId);
                await acceptSellOffer(accountAddress, nftId, offerId, "buy_offers");
            }
        }
        else {
            let response = await axios.get(
                `https://test-api.xrpldata.com/api/v1/xls20-nfts/offers/nft/${nftId}`);
            let sellArray = response.data.data.offers.sell;

            console.log("***********handleBuyItemClick", sellArray);

            if (!sellArray || !sellArray.length) {
                alert("No sell offer");
                return;
            }

            let offerJson = sellArray[0];

            let amount = parseInt(offerJson.Amount) / (10 ** 6);

            let alertString = "Are you sure want to buy this item from " + offerJson.Owner + " with " + amount + "Xrp?";
            if (confirm(alertString)) {
                console.log("*************handleBuyItemClick : Accept Sell Offer", accountAddress, nftId);
                await acceptSellOffer(accountAddress, nftId, offerJson.OfferID, "sell_offers");
            }
        }
    }


    // *********************ToMarcus******************************
    // ***********Handle Cancel Listing(Cancel Sell Offer)********
    // ***********************************************************
    async function handleCancelListingClick(nftId) {
        if (confirm("Are you sure want to cancel these sell offers for this token?")) {
            console.log("*************handleCancelListingClick : Cancel Sell Offer", accountAddress, nftId);
            await cancelOffer(accountAddress, nftId, "sell_offers");
        }
    }

    // *********************ToMarcus******************************
    // ***********Handle Cancel Bid(Cancel Buy Offer)********
    // ***********************************************************
    async function handleCancelBidClick(nftId) {
        if (confirm("Are you sure want to cancel these buy offers for this token?")) {
            console.log("*************handleCancelBidClick : Cancel Buy Offer", accountAddress, nftId);
            await cancelOffer(accountAddress, nftId, "buy_offers");
        }
    }

})(jQuery); // End of use strict