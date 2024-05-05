<?php
#----------------------------------------------------------------------
# Forked from https://github.com/stopforumspam/download-synology-dsm
#----------------------------------------------------------------------
# https://github.com/007revad/Download_Synology_Archive
#
# Checked at https://phpcodechecker.com/
#
# Run command
# php ./clone.php Os DSM 2>&1 | tee ./clone_$(date '+%Y%m%d-%H%M').log
#----------------------------------------------------------------------

$destination = "/volume1/downloads/archive.synology.com";

if (($argc != "3") || ($argv[1] == "Firmware")) {
    if ($argc < "2") {
        echo "Missing arguments!" . "\n";
    }
    elseif ($argc < "3") {
        echo "Missing argument!" . "\n";
    }
    elseif ($argc > "3") {
        echo "Too many arguments!" . "\n";
    }

    if ($argv[1] == "Firmware") {
        echo "Firmware needs to include the firmware type in <scrdir>" . "\n";
    }

    $i = 1;
    $args = "$argv[0]";
    while($i < $argc) {
        $args = $args . " \"" . "$argv[$i]" . "\"";
        $i++;
    }
    echo $args . "\n\n";

    echo "Usage: clone.php <srcdir> <subdir>" . "\n";
    echo "  Examples: " . "\n";
    echo "    php ./clone.php Os DSM" . "\n";
    echo "    php ./clone.php Package Docker" . "\n";
    echo "    php ./clone.php ToolChain \"Synology NAS GPL Source\"" . "\n\n";
    echo "To download all in the <srcdir> use All as the <subdir>" . "\n";
    echo "  Examples: " . "\n";
    echo "    php ./clone.php Os All" . "\n";
    echo "    php ./clone.php Package All" . "\n\n";
    echo "Firmware needs to include the firmware type in <scrdir>" . "\n";
    echo "  Examples: " . "\n";
    echo "    php ./clone.php Firmware/Camera BC500" . "\n";
    echo "    php ./clone.php Firmware/Camera TC500" . "\n";
    echo "    php ./clone.php Firmware/Camera All" . "\n\n";
    exit(1);
} else {
    $srcdir = "$argv[1]";
    $type = "$argv[2]";
    echo "------------------------------------------------------------ \n";
    //echo "Downloading: " . $srcdir . " > " . $type . "\n\n";
}

if(@chdir($destination)===true) { 
    echo "Current directory: " . getcwd() . "\n";
    sleep(5);  # Give me time to cancel if current directory incorrect
} else {
    echo "Change directory failed: " . $destination . "\n";
    exit(1);
}
echo "------------------------------------------------------------ \n";

function getLinks($url, $srcdir, $type, $dir) {

    //echo "url: $url" . "\n";  # debug

    $html = file_get_contents($url);
    if (empty($html)) {
        echo "file_get_contents command timed out!" . "\n";
        return;
    }
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    foreach ($dom->getElementsByTagName('a') as $node) {

        if (strpos($node->getAttribute("href"), ".pat") !== false  // Os
            || strpos($node->getAttribute("href"), ".spk") !== false  // Package
            || strpos($node->getAttribute("href"), ".bz2") !== false  // Utility
            || strpos($node->getAttribute("href"), ".deb") !== false  // Utility
            || strpos($node->getAttribute("href"), ".dmg") !== false  // Utility
            || strpos($node->getAttribute("href"), ".eap") !== false  // Utility
            || strpos($node->getAttribute("href"), ".elf") !== false  // Utility
            || strpos($node->getAttribute("href"), ".exe") !== false  // Utility
            || strpos($node->getAttribute("href"), ".gz") !== false   // Utility
            || strpos($node->getAttribute("href"), ".iso") !== false  // Utility
            || strpos($node->getAttribute("href"), ".msi") !== false  // Utility
            || strpos($node->getAttribute("href"), ".ova") !== false  // Utility
            || strpos($node->getAttribute("href"), ".pkg") !== false  // Utility
            || strpos($node->getAttribute("href"), ".rpm") !== false  // Utility
            || strpos($node->getAttribute("href"), ".vib") !== false  // Utility
            || strpos($node->getAttribute("href"), ".zip") !== false  // Utility
            || strpos($node->getAttribute("href"), ".apk") !== false  // Mobile
            || strpos($node->getAttribute("href"), ".crx") !== false  // ChromeApp
            || strpos($node->getAttribute("href"), ".txz") !== false  // ToolChain
            || strpos($node->getAttribute("href"), ".tbz") !== false  // ToolChain
            || strpos($node->getAttribute("href"), ".tgz") !== false  // ToolChain
            || strpos($node->getAttribute("href"), ".bin") !== false  // Firmware (Camera)
        ) {

            //echo "FILE: " . $node->getAttribute("href")."\n" ; 

            $remote = parse_url($node->getAttribute("href"));
            $fullpath = explode("/", $remote["path"]);
            $filename = urldecode(array_pop($fullpath));
            $path = "download/$srcdir/$type/$dir/";

            @mkdir($path, 0777, true);
            $dest = "$path$filename";

            # Getting remote file size
            $ch = curl_init($node->getAttribute("href"));
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $data = curl_exec($ch);
            curl_close($ch);

            $contentLength = 'unknown';
            $status = 'unknown';
            if (preg_match('/^HTTP\/(1\.[01]|2) (\d\d\d)/', $data, $matches)) {
                 $status = (int)$matches[2];
            }
            if (preg_match('/Content-Length: (\d+)/i', $data, $matches)) {
                 $contentLength = (int)$matches[1];
            }

            if ($status == 200 && $contentLength <> "unknown" && file_exists($dest) && (filesize($dest) != $contentLength)) {
                echo "Remote file is $contentLength however local file is " . filesize($dest) . ", Redownloading\n";
                @unlink($dest);
            }

            if (!file_exists($dest)) {
                //echo "downloading " . $node->getAttribute("href") . " to $dest \n";
                echo "Downloading: " . "$dest \n";
                // echo "From: " . $node->getAttribute("href") . "\n\n";

                $fh = fopen($dest, "w");
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/113.0 ");             
                curl_setopt($ch, CURLOPT_URL, $node->getAttribute("href"));
                curl_setopt($ch, CURLOPT_FILE, $fh);
                curl_exec($ch);
                curl_close($ch); 
//            } else {
//                // echo "Skipping download\n";
//                echo "Skipping: " . "$dest \n";
            }
        } elseif (strpos($node->getAttribute("href"), "/download/$srcdir/$type/") !== false) {
            echo "DIR: " . $node->getAttribute("href")."\n";
            $url = "https://archive.synology.com" .$node->getAttribute("href");
            getLinks($url, $srcdir, $type, $node->nodeValue);
        }
    }
}

function getDirs($url, $srcdir, $type) {
    global $types;
    $types = array();

    $html = file_get_contents($url);
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    foreach ($dom->getElementsByTagName('a') as $node) {

        $remote = parse_url($node->getAttribute("href"));
        $fullpath = explode("/", $remote["path"]);
        $type = urldecode(array_pop($fullpath));

        if (($type != "download") && (!str_contains($srcdir, $type))) {
            $types[] = $type;
            $path = "download/$srcdir/$type/";
        }
    }
}


$start = date('Y-m-d H:i:s');

if ($type != "All") {
    echo "Checking: " . $srcdir . "/" . $type . "\n";
    getLinks("https://archive.synology.com/download/$srcdir/$type", "$srcdir", "$type", "");
} else {
    echo "Checking: " . $srcdir . " > " . $type . "\n";
    getDirs("https://archive.synology.com/download/$srcdir", $srcdir, "");

    # Get array of types
    $type = "";
    $i = 0;
    while($i < count($types)) {
        echo "\nChecking: " . $srcdir . "/" . $types[$i] . "\n";
        getLinks("https://archive.synology.com/download/$srcdir/$types[$i]", "$srcdir", "$types[$i]", "");
        $i++;
    }
}

echo "------------------------------------------------------------ \n";
echo "Started:  $start \n";
echo "Finished: " . date('Y-m-d H:i:s') . "\n";
echo "------------------------------------------------------------ \n";
 
