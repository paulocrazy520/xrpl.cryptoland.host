const apiKey = "04b42479-cc50-4410-a783-1686eeebe65f";  //dev2
const apiSecret = "f53c6edc-1fb1-4c7f-8b79-f1ffef28037d"; // dev2
const issuerAddress = "rDUSz5wt8ZVENp7ZJq4qrv2f9A2h56Cf3b"; //for test

const xumm = new XummPkce(apiKey, {
    implicit: true, // Implicit: allows to e.g. move from social browser to stock browser
    redirectUrl: "https://sb236.cryptoland.host/xumm-return-payload.php",
});


