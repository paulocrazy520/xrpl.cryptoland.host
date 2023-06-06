const apiKey = env.API_KEY;
const apiSecret = env.API_SECRET;
const issuerAddress = env.DEFAULT_ISSUER_ADDRESS;

var signed_xumm_address = "";

const xumm = new XummPkce(apiKey, {
    implicit: true, // Implicit: allows to e.g. move from social browser to stock browser
    redirectUrl: env.REDIRECT_URL,
});

/*=======================================================*/
/*--------------Get User Info ---------------------------*/
/*=======================================================*/
async function getUserInfo() {
  const response = await axios.post('jeffajax.php', {
    type: "GetUserInfo",
  });

  console.log("**************getUserInfo Respone from getUserInfo", response);
  if (response)
    return response.data;

}

/*============================================================*/
/*-----------------------Xumm wallet sign in-------------------*/
/*============================================================*/
function signIn() {
  xumm.authorize().catch((e) => {
    console.log("e", e);
  });
}

// *******************************************************
// *******************Create Buy Offer ******************
// *******************************************************
async function createBuyOffer(
  owner,
  account,
  tokenIdField,
  amountField,
  expirationField
) {
  if (!amountField || !tokenIdField)
    return;

  var expirationDate = null
  if (expirationField) {
    var days = expirationField
    let d = new Date()
    d.setDate(d.getDate() + parseInt(days))
    var expirationDate = xrpl.isoTimeToRippleTime(d)
  }

  var transactionBlob = {
    txjson: {
      "Owner": owner,
      "TransactionType": "NFTokenCreateOffer",
      "Account": account,
      "NFTokenID": tokenIdField,
      "Amount": String(parseFloat(amountField) * (10 ** 6)),
      "Flags": "0"
    }
  }

  if (expirationDate != null) {
    transactionBlob.txjson.Expiration = expirationDate
  }

  await postPayload(transactionBlob)
}// End of createBuyOffer()



// *******************************************************
// ****************** Create Sell Offer ******************
// *******************************************************
async function createSellOffer(
  account,
  tokenIdField,
  amountField,
  expirationField
) {

  if (!amountField || !tokenIdField)
    return;

  var expirationDate = null
  if (expirationField) {
    var days = expirationField
    let d = new Date()
    d.setDate(d.getDate() + parseInt(days))
    var expirationDate = xrpl.isoTimeToRippleTime(d)
  }

  var transactionBlob = {
    txjson: {
      "TransactionType": "NFTokenCreateOffer",
      "Account": account,
      "NFTokenID": tokenIdField,
      "Amount": String(parseFloat(amountField) * (10 ** 6)),
      "Flags": "1"
    }
  }

  if (expirationDate != null) {
    transactionBlob.txjson.Expiration = expirationDate
  }

  await postPayload(transactionBlob)
}// End of createSellOffer()

// *******************************************************
// ****************** Accept Sell Offer ******************
// *******************************************************
async function acceptSellOffer(account,
  nftTokenId, nftOfferID, tableName = "sell_offers"
) {

  var transactionBlob = null;

  if (tableName == "sell_offers") {
    transactionBlob = {
      txjson: {
        "TransactionType": "NFTokenAcceptOffer",
        "Account": account,
        "NFTokenSellOffer": nftOfferID
      }
    }
  }
  else if (tableName == "buy_offers") {
    transactionBlob = {
      txjson: {
        "TransactionType": "NFTokenAcceptOffer",
        "Account": account,
        "NFTokenBuyOffer": nftOfferID
      }
    }
  }
  else if (tableName == "claim_offers") {
    transactionBlob = {
      txjson: {
        "TransactionType": "NFTokenAcceptOffer",
        "Account": account,
        "NFTokenSellOffer": nftOfferID
      }
    }
  }

  postPayload(transactionBlob, nftTokenId, tableName);
}// End of acceptSellOffer()

// *******************************************************
// ******************** Cancel Offer *********************
// *******************************************************
async function cancelOffer(account,
  nftTokenId, tableName = "sell_offers"
) {
  //Prepare Expiration Date------------------------------------- 
  var transactionBlob = {
    txjson: {
      "TransactionType": "NFTokenCancelOffer",
      "Account": account
      // "NFTokenOffers": This is specified from jeffajax.php
    }
  }

  postPayload(transactionBlob, nftTokenId, tableName);
}// End of cancelOffer()

/*=============================================================================*/
/*--------------------Post payload via xumm on php server----------------------*/
/*=============================================================================*/
async function postPayload(transactionBlob, offeredNftTokenId = undefined, tableName = undefined) {
  console.log("******postPayload*******", transactionBlob, offeredNftTokenId, tableName);
  $('.cs-preloader').delay(10).fadeIn('slow'); //Show loading screen

  axios.post('jeffajax.php', {
    type: "SubscribePayload",
    payload: transactionBlob,
    offeredNftTokenId: offeredNftTokenId,
    tableName: tableName
  })
    .then(response => {
      // console.log(response.data);
      //location.reload();
      console.log('*********************postPayload Response*=', response, tableName, offeredNftTokenId);
      if (tableName == "claim_offers") {
        if (response.data == true) {
          $('.cs-isotop_item[nft-id="' + offeredNftTokenId + '"]').removeClass('unclaimed').addClass('unrevealed');

          $('.cs-action_item[nft-id="' + offeredNftTokenId + '"]').removeClass('cs-card_btn_4').addClass('cs-card_btn_2');
          $('.cs-action_item[nft-id="' + offeredNftTokenId + '"]').attr('data-modal', '#revealItem');
          $('.cs-action_item[nft-id="' + offeredNftTokenId + '"] span').text('Reveal');

          
          if ($('#unclaimedCount').val() > 0) {
            $('#unclaimedCount').val(parseInt($('#unclaimedCount').val()) - 1);
            $('#unrevealedCount').val(parseInt($('#unrevealedCount').val()) + 1);
          }

          $('.cs-isotop').isotope('reloadItems').isotope('layout');

          setTimeout(function () {
            isotopInit();
          }, 1000);
        }
      }
      else {
        location.reload();
      }
      $('.cs-preloader').delay(10).fadeOut('slow'); //Show loading screen
    })
    .catch(error => {
      console.error(error);
      $('.cs-preloader').delay(10).fadeOut('slow'); //Show loading screen
    });
}


/*=============================================================================*/
/*-----------------------------------Login--------------------------------------*/
/*=============================================================================*/
function LoginUser(user_name, user_password) {
  console.log("******LoginUser*******", user_name, user_password);

  axios.post('jeffajax.php', {
    type: "Login",
    user_name: user_name,
    user_password: user_password
  })
    .then(response => {
      console.log('*********************LoginUser Response=', response.data);
      if (response.data) {
        alert("Login successfully! You logged with id " + response.data);
        //Refresh page after signing offer
        location.reload();
      }
      else {
        alert("Please input correct user name and password!");
      }

    })
    .catch(error => {
      console.error(error);
    });
}


/*=============================================================================*/
/*-----------------------------------Logout--------------------------------------*/
/*=============================================================================*/
function LogoutUser() {
  console.log("******LooutUser*******");

  axios.post('jeffajax.php', {
    type: "Logout"
  })
    .then(response => {
      console.log('*********************Logout Response=', response.data);
      if (response.data) {
        alert("Logout successfully!");
        //xumm.logout();
      }
      //Refresh page after signing offer
      location.reload();

    })
    .catch(error => {
      console.error(error);
    });
}

// ******************** Claim Offer is for test, not used*********************
async function claimOffer(standbyBuyerField,
  tokenIdField
) {
  // Prepare transaction -------------------------------------------------------
  const state = await xumm.state();

  
  if (!state?.me?.sub || standbyBuyerField != state.me.sub) {
  
    alert("Verify Xumm Auth!");
    return null;
  }

  uuid = "0ce4ac8d-eb2e-49d8-a4c4-149d7e10d540";
  tid = "000927107E8881FA7D8A97D316DFA235E76749D97EAADC4744B17C9E00000003";

  axios.post("http://95.217.98.125:26650/claim/offer", { tid: tid, uuid: uuid }).then((result) => {

    console.log(result.data);

    const offerId = result.data.offerId;

    var transactionBlob = {
      txjson: {
        "TransactionType": "NFTokenAcceptOffer",
        "Account": standbyBuyerField,
        "NFTokenBuyOffer": offerId
      }
    }
    //server_url

    // End of cancelOffer()
  })
}