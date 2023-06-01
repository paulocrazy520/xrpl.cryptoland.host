(function ($) {
    'use strict';

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

            default:
                break;
        }

    })

    // *********************ToMarcus**************************
    // ***********Handle List Item(Create Sell Offer)********
    // *******************************************************
    async function handleListItemClick() {
        var modalId = '#listItem';
        var nftId = $(modalId).find('#nft_id').text();
        var amount = $(modalId).find('#bid_quantity').val();

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
        var amount = $(modalId).find('#bid_quantity').val();
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
        // {
        //     "Amount": "23000000",
        //     "Flags": 1,
        //     "NFTokenID": "0009271085CBC8D60891F35B19C107477B0A7BA29CAAB8E60000099B00000000",
        //     "OfferID": "10BF1C473EEE6618AF681DB2DC331D73685A8880176A74F5AA98F324BD06547E",
        //     "Owner": "r97nyzoijsUYp5CQUQNf8dMzh4XyqZpFCU",
        //     "Destination": null,
        //     "Expiration": null
        //   }

        //get offer string from nftId

        if(offerId){
            let response = await axios.get(
                `https://test-api.xrpldata.com/api/v1/xls20-nfts/offer/id/${offerId}`);
            let offer = response.data.data.offer;

            console.log("***********handleBuyItemClick: OfferId", offer);
            
            if(!offer){
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
        else
        {
            let response = await axios.get(
                `https://test-api.xrpldata.com/api/v1/xls20-nfts/offers/nft/${nftId}`);
            let sellArray = response.data.data.offers.sell;

            console.log("***********handleBuyItemClick", sellArray);
            
            if(!sellArray || !sellArray.length){
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
}) (jQuery); // End of use strict