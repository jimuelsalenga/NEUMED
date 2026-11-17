<?php
session_start();
include('includes/db_conn.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$msg = "";

if (isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = $_POST['role'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (fullname, email, role, password) VALUES ('$name', '$email', '$role', '$pass')";
    if ($conn->query($sql)) {
        $msg = "<div class='alert alert-success py-2 small'>User added successfully!</div>";
    }
}

if (isset($_POST['edit_user'])) {
    $uid = $_POST['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = $_POST['role'];

    $sql = "UPDATE users SET fullname='$name', email='$email', role='$role' WHERE id='$uid'";
    if ($conn->query($sql)) {
        $msg = "<div class='alert alert-success py-2 small'>User updated successfully!</div>";
    }
}

if (isset($_GET['delete_id'])) {
    $did = $_GET['delete_id'];
    if ($conn->query("DELETE FROM users WHERE id='$did'")) {
        header("Location: manage_users.php?msg=deleted");
        exit();
    }
}

if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $msg = "<div class='alert alert-warning py-2 small'>User deleted successfully!</div>";
}

$users = $conn->query("SELECT id, fullname, role, email FROM users WHERE id != ".$_SESSION['user_id']." ORDER BY fullname ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .user-card { transition: 0.2s; border-radius: 15px; }
        .btn-round { border-radius: 50px; }
    </style>
</head>
<body class="bg-light">

    <div class="p-3 bg-white border-bottom mb-4 d-flex justify-content-between align-items-center sticky-top shadow-sm">
        <div class="d-flex align-items-center">
            <a href="admin_dashboard.php" class="btn btn-light rounded-circle me-3">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="fw-bold">User Access Control</span>
        </div>
        <button class="btn btn-primary btn-round px-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-1"></i> Add User
        </button>
    </div>

    <div class="container">
        <?php echo $msg; ?>

        <div class="row g-3">
            <?php while($u = $users->fetch_assoc()): ?>
                <div class="col-12 col-md-6">
                    <div class="card border-0 shadow-sm p-3 user-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-0 fw-bold"><?php echo $u['fullname']; ?></h6>
                                <small class="text-muted d-block"><?php echo $u['email']; ?></small>
                                <span class="badge bg-light text-dark border mt-2"><?php echo $u['role']; ?></span>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm rounded-3">
                                    <li><a class="dropdown-item py-2" href="#" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $u['id']; ?>"><i class="fas fa-edit me-2 text-primary"></i> Edit</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2 text-danger" href="?delete_id=<?php echo $u['id']; ?>" onclick="return confirm('Are you sure?')"><i class="fas fa-trash me-2"></i> Delete</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="editModal<?php echo $u['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 rounded-4 shadow">
                            <div class="modal-header border-0">
                                <h6 class="modal-title fw-bold">Edit User</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="" method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <div class="mb-3">
                                        <label class="small fw-bold">Full Name</label>
                                        <input type="text" name="fullname" class="form-control border-0 bg-light" value="<?php echo $u['fullname']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="small fw-bold">Email</label>
                                        <input type="email" name="email" class="form-control border-0 bg-light" value="<?php echo $u['email']; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="small fw-bold">Role</label>
                                        <select name="role" class="form-select border-0 bg-light">
                                            <option value="Student" <?php if($u['role']=='Student') echo 'selected'; ?>>Student</option>
                                            <option value="Medical Staff" <?php if($u['role']=='Medical Staff') echo 'selected'; ?>>Medical Staff</option>
                                            <!--option value="Enrollment Officer" <?php if($u['role']=='Enrollment Officer') echo 'selected'; ?>>Enrollment Officer</option-->
                                            <option value="Admin" <?php if($u['role']=='Admin') echo 'selected'; ?>>Admin</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="submit" name="edit_user" class="btn btn-dark w-100 rounded-pill">Update User</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-bold">Register New User</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <input type="text" name="fullname" class="form-control border-0 bg-light py-2" placeholder="Full Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control border-0 bg-light py-2" placeholder="Email Address" required>
                        </div>
                        <div class="mb-3">
                            <select name="role" class="form-select border-0 bg-light py-2">
                                <option value="Student">Student</option>
                                <option value="Medical Staff">Medical Staff</option>
                                <!--option value="Enrollment Officer">Enrollment Officer</option-->
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <input type="password" name="password" class="form-control border-0 bg-light py-2" placeholder="Temporary Password" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="add_user" class="btn btn-primary w-100 rounded-pill">Register User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>