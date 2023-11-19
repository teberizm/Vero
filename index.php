<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Task Viewer</title>
  <style>
    table {
      border-collapse: collapse;
      width: 100%;
    }
    th, td {
      border: 1px solid #dddddd;
      text-align: left;
      padding: 8px;
    }
    th {
      background-color: #f2f2f2;
    }
    #colorCode {
      width: 20px;
      height: 20px;
      display: inline-block;
    }
    #modal {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      padding: 20px;
      background-color: #fff;
      z-index: 999;
    }
    #selectedImage {
      max-width: 100%;
      max-height: 300px;
      margin-top: 10px;
    }
  </style>
</head>
<body>

<?php
// Function to authenticate and get the access token
function getAccessToken() {
  $url = "https://api.baubuddy.de/index.php/login";
  $data = [
    "username" => "365",
    "password" => "1",
  ];
  $headers = [
    "Authorization: Basic QVBJX0V4cGxvcmVyOjEyMzQ1NmlzQUxhbWVQYXNz",
    "Content-Type: application/json",
  ];

  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => $headers,
  ]);

  $response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);

  if ($err) {
    echo "cURL Error #:" . $err;
    return null;
  } else {
    $json = json_decode($response, true);
    return $json["oauth"]["access_token"];
  }
}

// Function to get tasks data
function getTasksData($accessToken) {
  $url = "https://api.baubuddy.de/dev/index.php/v1/tasks/select";

  $headers = [
    "Authorization: Bearer " . $accessToken,
  ];

  $curl = curl_init();
  curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_HTTPHEADER => $headers,
  ]);

  $response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);

  if ($err) {
    echo "cURL Error #:" . $err;
    return null;
  } else {
    return json_decode($response, true);
  }
}

// Get access token
$accessToken = getAccessToken();

// If access token is available, get tasks data
if ($accessToken) {
  $tasksData = getTasksData($accessToken);

  // Display tasks table
  if ($tasksData) {
    echo '<input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search for tasks...">
          <table id="tasksTable">
              <th>Task</th>
              <th>Title</th>
              <th>Description</th>
              <th>Color Code</th>
            </tr>';

    foreach ($tasksData as $task) {
      echo '<tr>
              <td>' . $task["task"] . '</td>
              <td>' . $task["title"] . '</td>
              <td>' . $task["description"] . '</td>
              <td><div id="colorCode" style="background-color: ' . $task["colorCode"] . '"></div></td>
            </tr>';
    }

    echo '</table>';
  }

   echo '<button onclick="openModal()">Open Modal</button>';

  // Modal with image selection
  echo '<div id="modal">
          <button onclick="selectImage()">Select Image</button>
          <input type="file" id="imageInput" style="display: none;" accept="image/*">
          <img id="selectedImage" src="" alt="Selected Image">
        </div>';

 // Auto-refresh every 60 minutes
  echo '<script>
          setInterval(function () {
            fetchTasksData();
          }, 3600000); // 60 minutes in milliseconds

          function fetchTasksData() {
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function () {
              if (xhr.readyState == 4 && xhr.status == 200) {
                var newTasksData = JSON.parse(xhr.responseText);
                updateTable(newTasksData);
              }
            };
            xhr.open("GET", "https://api.baubuddy.de/dev/index.php/v1/tasks/select", true);
            xhr.setRequestHeader("Authorization", "Bearer ' . $accessToken . '");
            xhr.send();
          }

          // Function to update the table with new data
          function updateTable(newTasksData) {
            var table = document.getElementById("tasksTable");
            table.innerHTML = \'<tr><th>Task</th><th>Title</th><th>Description</th><th>Color Code</th></tr>\';
            for (var i = 0; i < newTasksData.length; i++) {
              var task = newTasksData[i];
              var row = table.insertRow(-1);
              var cell1 = row.insertCell(0);
              var cell2 = row.insertCell(1);
              var cell3 = row.insertCell(2);
              var cell4 = row.insertCell(3);
              cell1.innerHTML = task.task;
              cell2.innerHTML = task.title;
              cell3.innerHTML = task.description;
              cell4.innerHTML = \'<div id="colorCode" style="background-color: #\' + task.colorCode + \'"></div>\';
            }
          }
        </script>';
        
}
?>
<script>
   // Function to open modal
  function openModal() {
    document.getElementById('modal').style.display = 'block';
  }

  // Function to close modal
  function closeModal() {
    document.getElementById('modal').style.display = 'none';
  }

  // Function to select image
  function selectImage() {
    var imageInput = document.getElementById('imageInput');
    imageInput.click();

    imageInput.addEventListener('change', function () {
      var selectedImage = document.getElementById('selectedImage');
      var file = imageInput.files[0];

      if (file) {
        var reader = new FileReader();

        reader.onload = function (e) {
          selectedImage.src = e.target.result;
        };

        reader.readAsDataURL(file);
      }
    });
  }

  // Function to search the table
    function searchTable() {
    var input, filter, table, tr, td, i, j, txtValue;
    input = document.getElementById("searchInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("tasksTable");
    tr = table.getElementsByTagName("tr");

    for (i = 0; i < tr.length; i++) {
      txtValue = "";
      for (j = 0; j < tr[i].getElementsByTagName("td").length; j++) {
        td = tr[i].getElementsByTagName("td")[j];
        if (td) {
          txtValue += td.textContent || td.innerText;
        }
      }
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }
</script>

</body>
</html>
