CREATE DATABASE IF NOT EXISTS student_habits_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE student_habits_db;

-- Keep user_id foreign keys aligned with this primary key: INT UNSIGNED.
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS weekly_themes (
  user_id INT UNSIGNED NOT NULL,
  theme VARCHAR(100) NOT NULL,
  verse_reference VARCHAR(100) NOT NULL,
  verse_text TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_weekly_themes_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS habits (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  frequency ENUM('daily', 'weekly') NOT NULL,
  category ENUM('academic', 'health', 'admin', 'social') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_habits_user_id (user_id),
  CONSTRAINT fk_habits_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS habit_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  habit_id INT UNSIGNED NOT NULL,
  date_logged DATE NOT NULL,
  status ENUM('completed', 'partial', 'grace_day') NOT NULL,
  PRIMARY KEY (id),
  KEY idx_habit_logs_habit_id (habit_id),
  UNIQUE KEY uq_habit_logs_habit_date (habit_id, date_logged),
  CONSTRAINT fk_habit_logs_habit
    FOREIGN KEY (habit_id) REFERENCES habits (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS timetable_events (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  class_name VARCHAR(255) NOT NULL,
  day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  color_code VARCHAR(20) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_timetable_events_user_id (user_id),
  CONSTRAINT fk_timetable_events_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assignments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  -- Must match users.id exactly, including UNSIGNED.
  user_id INT UNSIGNED NOT NULL,
  unit_code VARCHAR(50) NOT NULL,
  unit_name VARCHAR(255) NOT NULL,
  task VARCHAR(500) NOT NULL,
  due_date DATE NOT NULL,
  is_completed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_assignments_user_id (user_id),
  KEY idx_assignments_user_status (user_id, is_completed),
  CONSTRAINT fk_assignments_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
) ENGINE = InnoDB;
