const fs = require('fs');
const path = require('path');
// Recursively search the directory for files with the given extension
function searchFiles(dir, ext) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(function (file) {
        file = path.join(dir, file);
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) {
            // Recurse into a subdirectory
            results = results.concat(searchFiles(file, ext));
        } else {
            // Check if the file extension matches
            if (file.endsWith(ext)) {
                results.push(file);
            }
        }
    });
    return results;
}
// Modify the contents of each file that matches the extension
function modifyFiles(files, callback) {
    files.forEach(function (file) {
        const contents = fs.readFileSync(file, 'utf8');
        const modifiedContents = callback(contents);
        fs.writeFileSync(file, modifiedContents);
    });
}

// Search and Modify files
const files = searchFiles('./testNet', '.json');
console.log(files);
modifyFiles(files, function (contents) {
    // Replace all occurrences of "foo" with "bar"
    return contents.replaceAll("https://ingameassets.cryptoland.host/testNet", "https://sb236.cryptoland.host/testNet");
});

//Searc hfolders

// Function to recursively search for folders and files
function walk(dir, callback) {
    fs.readdirSync(dir).forEach(file => {
        const filepath = path.join(dir, file);
        if (fs.statSync(filepath).isDirectory()) {
            walk(filepath, callback);
        }
        callback(filepath);
    });
}

// Replace folder names or create new ones on specific path
const basePath = './testNet';

walk(basePath, filepath => {
    if (fs.statSync(filepath).isDirectory()) {
        const dirname = path.basename(filepath);
        console.log(filepath);

        let liveName = dirname;

        if (dirname == "unrevealed-json")
            liveName = dirname.replace("unrevealed-json", "live-metadata");
        else if (dirname == "revealed-json")
            liveName = dirname.replace("revealed-json", "revealed-metadata");

        const renamedPath = path.join(path.dirname(filepath), liveName); // Renamed new path

        if (liveName !== dirname) {
            fs.renameSync(filepath, renamedPath); // Rename folder
        } else {
            if (dirname == "box" || dirname == "cryptopian" || dirname == "land" || dirname == "lbk" || dirname == "vials") {
                const renamedPath = path.join(filepath, "archived-metadata"); // Renamed new path
                fs.mkdirSync(renamedPath); // Create new folder
            }
        }
    }
});
