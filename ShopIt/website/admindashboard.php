<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Database Dashboard</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #3f403b;
    }
    header {
      color: white; 
      text-align: center;
      border: black solid;
      font-size: x-large;
    }
    .container {
      display: flex;
      min-height: 100vh;
      color: white;
      border: black solid;
    }
    .sidebar {
      width: 220px;
      background-color: #82a8b8;
      color: black;
      padding: 20px;
      border: black solid;
    }
    .sidebar h2 {
      font-size: 1.2rem;
      margin-bottom: 20px;
    }
    .sidebar a {
      display: block;
      color: white;
      font-size: x-large;
      text-decoration: none;
      margin: 10px 0;
      border: #3f403b solid;
      background-color: #3f403b;
    }
    .sidebar a:hover{
      color: aqua;
    }
    .main {
      flex: 1;
      padding: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #82a8b8;
      color: black;
    }
    th, td {
      border: 1px solid black;
      padding: 10px;
      text-align: left;
      
    }
    th {
        background-color: #82a8b8;
    }
    .edit-btn {
      background: green;
      color: black;
      border: none;
      padding: 5px 10px;
      border-radius: 4px;
      cursor: pointer;
    }
    .edit-btn:hover{
      background-color: lightgreen;
    }
    .account-settings {
      margin-top: 30px;
    }
    .account-settings h3 {
      margin-bottom: 10px;
    }
    .account-settings input {
      display: block;
      margin-bottom: 10px;
      padding: 8px;
      width: 100%;
      max-width: 300px;
      background-color: #82a8b8;
    }
    .account-settings input::placeholder{
        color: black;
    }
    .save-btn {
      background: green;
      color: black;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .save-btn:hover{
      background-color: lightgreen;
    }
  </style>
</head>
<body>
    <header>
        <h1>Dashboard</h1>
    </header>
  <div class="container">
    <div class="sidebar">
      <a href="admindashboard">Dashboard</a>
      <a href="accountSetting">Account Settings</a>
      <a href="adminlogin">Logout</a>
    </div>
    <div class="main">
      <h2>Database Records</h2>
      <?php include 'fake_database.php'; ?>
      
      <div class="account-settings">
        <h3>Edit Account</h3>
        <FROM action="update_data.php" method="post">
        <input type="text" id="ID" placeholder="ID">
        <input type="text" id="First_Name" placeholder="First Name" />
        <input type="email" id="Last_name" placeholder="Last Name" />
        <input type="password" id="Update-Password" placeholder="Update-Password" />
        <button class="save-btn">Save Changes</button>
      </FROM>
      </div>
    </div>
  </div>
</body>
</html>
