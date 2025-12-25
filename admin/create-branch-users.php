<?php
require_once 'config.php'; // Ensure this connects to attendance_db

function createUser($username, $plainPassword, $role, $fullName, $branch, $employeeCode) {
    global $conn;
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
    
    // Check for duplicates
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR employee_code = ?");
    $check->bind_param("ss", $username, $employeeCode);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        echo "⚠️ Skipped: $username or $employeeCode already exists<br>";
        $check->close();
        return;
    }
    $check->close();
    
    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, role, full_name, assigned_branch, employee_code)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssss", $username, $hashedPassword, $role, $fullName, $branch, $employeeCode);
    
    if ($stmt->execute()) {
        echo "✅ Created: $username ($branch) - Code: $employeeCode<br>";
    } else {
        echo "❌ Error for $username: " . $stmt->error . "<br>";
    }
    $stmt->close();
}

function createUsersFromList($userList, $branchPrefix, $branchName) {
    foreach ($userList as [$fullName, $employeeCode]) {
        // Clean and generate unique username
        $cleanName = strtolower(str_replace(' ', '', $fullName));
        $username = strtolower($branchPrefix . '_' . $cleanName . '_' . strtolower($employeeCode));
        $password = "agent123"; // Default password
        
        createUser($username, $password, 'agent', $fullName, $branchName, $employeeCode);
    }
}

// 🧑‍🏫 Define users for Prince Gupta A40
//$princeA40Users = [
    //["jitender", "IBC1003"],
    // Add more users for A40 here
    //["Kanchan", "IBCE0060"],
  //  ["Markandey Pandey","IBCE0015"],
   // ["Lalita Rani","IBCC0281"],
    //["Hia Dan","IBCC1573"],
    //["Kishan Paswan","IBCC2489"],
    //["Kunal Sharma","IBCC1762"],
    //["Rahul Mittal","IBCE0027"],
    //["Ramesh Kumar Sharma","IBCC0278"],
   // ["Kanchan Bhandari","IBCC1586"],
    //["Shrejal Kasaudhan","IBCC0639"],
 //   ["Mili","IBCC2353"],
   // ["Astha Vishwakarma","IBCC0412"],
   // ["Bharat Singh","IBCC1767"],
    //["Kajal Singh","IBCC2490"],
    //["Avinash Kumar","IBCE0024"],
    //["Shivam Kumar Thakur","IBCE0450"],
    //["Manu Kumari","IBCC1732"],
    //["Pooja","IBCC0414"],
    //["Bhaskar","IBCC0101"],
    //["Lokesh Kumar","IBCC1667"],
   // ["Pankaj Singh","IBCC2491"],
    //["Aditya Kumar Chaubey","IBCC2492"],
    //["Kamlawati Kumari","IBCC2493"],
    //["Shruti Tyagi","IBCC2494"],
   // ["Swati Saxena","IBCE0023"],
   // ["Ravi Shankar","IBCC1679"],
    //["Ram Singh","IBCE0291"],
    //["Prakhar Raj","IBCC2357"],
    //["Ajmer Singh","IBCC2355"],
    //["Neha","IBCE0152"],
    //["Devendra Kumar","IBCC1527"],
    //["Aman Kumar","IBCC2495"],
    //["Mohd Haris Hussain","IBCE0131"],
    //["Rahul Kumar Singh","IBCC1981"],
    //["Vipin Maurya","IBCC1327"],
    //["Sumit Kumar","IBCC2133"],
    //["Aniket kumar Singh","IBCC2359"],
    //["Abdul salam","IBCE0058"],
    //["Mahima Choudhary","IBCC0640"],
    //["Pritika Kumari","IBCC2279"],
    //["Surjeet Kumar","IBCC2496"],
    //["Zeenat Zahan","IBCC0890"],
    //["Amisha Halder","IBCC2358"],
    //["Ekta Singh","IBCE0422"],
    //["Pushpa","IBCC2360"],
    //["Varsha Kumari","IBCC2361"],
    //["Mohd Saddam","IBCC2362"],
    //["Khushboo","IBCC2363"],
    //["Tithi Priya","IBCC2364"],
    //["Saima Khatun","IBCC2497"],
    //["Preeti","IBCC2498"],
    //["Baidhnath Sharma","IBCC2499"],
    //["Nitin kumar","IBCC2500"],
    //["Ashish Kumar","IBCC2501"],
    //["Anjali Sharma","IBCC2367"],
    //["Hariom","IBCC2502"],
    //["Anuradha","IBCE0260"],
    //["Dipesh","IBCE0059"],
    //["Amit Kumar","IBCC1221"],
    //["Avneesh Kumar","IBCC2503"],
//];

// 🧑‍🏫 Define users for Prince Gupta B78  
//$princeB78Users = [
    // Add users for B78 here
    // ["Karan Surya", "IBCE0055"],
     //["Gulshan ", "IBCC1984"],
    //["Harshit Sharma","IBCC0032"],
    //["Suraj","IBCC0665"],
    //["Javed","IBCC2145"],
    //["Sagar Thakur","IBCE0305"],
    //["Deepak Kumar","IBCC1579"],
    //["Prachi Rai","IBCE0234"],
    //["Shivani Kumari","IBCC0599"],
    //["Jaypal Singh","IBCC2284"],
    //["Anjali Arya","IBCC2504"],
    //["Priya","IBCE0190"],
    //["Deepak Bhatt","IBCE0423"],
    //["Archana Kumari","IBCC2505"],
    //["Punita Jha","IBCC2506"],
    //["Pankaj Bhatt","IBCE0424"],
    //["Aaradhya Bharti","IBCC2385"],
    //["Shivani Kumari","IBCC2386"],
    //["Kaushik Vats","IBCC2507"],
    //["Aniket Sharma","IBCC2508"],
    //["Shubham panokhar","IBCC2509"],
    //["Pintu Kumar","IBCC2389"],
    //["Shipra Singh","IBCE0451"],
    //["Ravinder Kumar Pal","IBCE0452"],
    //["Piyush Sharma","IBCC2510"],
    //["Akash Maurya","IBCC2511"],
  //  ["Tanya","IBCC2512"],

//    ["Manisha Priyadarshani","IBCC2513"],
  //  ["Geeti Dadwal","IBCC2514"],
//    ["Renu Pal","IBCC2515"],
 //   ["Pooja Yadav","IBCE0453"],
 //  ["Sawan Kumar","IBCC2517"],
 //   ["Dev Kumar","IBCC2518"],
  //  ["Chetan","IBCE0235"],
   // ["Punit","IBCC1432"],
    //["Ankit Bharara","IBCC1347"],
    //["Alfez Khan","IBCC1591"],
    //["Ankit Sharma","IBCC2376"],
    //["Aditya Kumar","IBCC2378"],
    //["Mayank bhardwaj","IBCE0323"],
    //["Abhishek Dwivedi","IBCE0324"],
    //["Prince Tomar","IBCC1771"],
    //["Supriya","IBCC1772"],
    //["Sumit Kumar","IBCC1876"],
    //["Manasavi","IBCC1875"],
    //["Ashish","IBCC2293"],
    //["Uwais","IBCC1992"],
    //["Dev","IBCC2150"],
    //["Nafees","IBCC2152"],
    //["Shivansh Dwivedi","IBCC2382"],
    //["Riya","IBCC2294"],
    //["Dharmender","IBCE0454"],
    //["Anmol Gupta","IBCE0353"],
    //["Pranjal Sharma","IBCC2519"],
    //["Akash Officeboy","IBCC1121"],
    //["Vishal Officeboy","IBCC2153"],
    //["Jitender Singh","IBCE0238"],
    //["khushboo verma","IBC5001"],

//];
//$codexateamUsers = [
   // ["Nawal Kishor","IBC8411"],
   //["sunidhi kishor","IBC8412"],
   //["ragni kumari", "IBC8413"],
   //["angel singh","IBC8414"],
   //["devanshi","IBC8415"],
   //["arti kumari","IBC8516"],
   //["Rehmaan","IBC8517"],
   //["faiz","IBC8518"],
   //["astha","IBC8519"],
   //["swati raut","IBC8520"],
  // ["jiya singh","IBC8521"],
//];

$rohitUsers = [
   // ["priya singh", "IBC2001"],
    ["om kashyap","IBC2002"],
];


// 🏁 Create users for each branch
//echo "<h3>Creating users for Prince Gupta A40:</h3>";
//createUsersFromList($princeA40Users, "princeA40", "Prince Gupta A40");

//echo "<h3>Creating users for Prince Gupta B78:</h3>";
//createUsersFromList($princeB78Users, "princeB78", "Prince Gupta B78");

//echo "<h3>Creating users for Codexa Team Users:</h3>";
//createUsersFromList($codexateamUsers, "codexateam", "Codexa Team");

createUsersFromList($rohitUsers, "rohit", "Rohit");


echo "<br>🎉 All users processed.";
?>
