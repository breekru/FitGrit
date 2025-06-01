<?php
// FitGrit Data Handler
// Handles all JSON file operations for data storage and retrieval

// Prevent direct access
if (!defined('FITGRIT_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Read JSON data from file
 * @param string $filePath Full path to JSON file
 * @return array|false Data array or false on failure
 */
function readJsonFile($filePath) {
    try {
        if (!file_exists($filePath)) {
            return [];
        }
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            logActivity("Failed to read file: $filePath", 'ERROR');
            return false;
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logActivity("JSON decode error in file $filePath: " . json_last_error_msg(), 'ERROR');
            return false;
        }
        
        return $data;
        
    } catch (Exception $e) {
        logActivity("Exception reading file $filePath: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Write JSON data to file
 * @param string $filePath Full path to JSON file
 * @param array $data Data to write
 * @param bool $backup Create backup before writing
 * @return bool True on success
 */
function writeJsonFile($filePath, $data, $backup = true) {
    try {
        // Create backup if requested and file exists
        if ($backup && file_exists($filePath) && BACKUP_ENABLED) {
            $backupPath = $filePath . '.backup.' . date('Y-m-d-H-i-s');
            copy($filePath, $backupPath);
            
            // Clean old backups
            cleanOldBackups(dirname($filePath));
        }
        
        // Ensure directory exists
        $directory = dirname($filePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Write data with file locking
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            logActivity("JSON encode error: " . json_last_error_msg(), 'ERROR');
            return false;
        }
        
        $result = file_put_contents($filePath, $json, LOCK_EX);
        if ($result === false) {
            logActivity("Failed to write file: $filePath", 'ERROR');
            return false;
        }
        
        // Set proper file permissions
        chmod($filePath, 0644);
        
        return true;
        
    } catch (Exception $e) {
        logActivity("Exception writing file $filePath: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Clean old backup files
 * @param string $directory Directory to clean
 */
function cleanOldBackups($directory) {
    $files = glob($directory . '/*.backup.*');
    $cutoffTime = time() - (BACKUP_RETENTION_DAYS * 24 * 3600);
    
    foreach ($files as $file) {
        if (filemtime($file) < $cutoffTime) {
            unlink($file);
        }
    }
}

/**
 * Get user data
 * @param string $userId User ID
 * @return array|false User data or false on failure
 */
function getUserData($userId) {
    $filePath = USERS_PATH . $userId . '.json';
    return readJsonFile($filePath);
}

/**
 * Save user data
 * @param string $userId User ID
 * @param array $data User data
 * @return bool True on success
 */
function saveUserData($userId, $data) {
    $filePath = USERS_PATH . $userId . '.json';
    $data['updated_at'] = date('Y-m-d H:i:s');
    return writeJsonFile($filePath, $data);
}

/**
 * Get weight data for user
 * @param string $userId User ID
 * @param int $limit Number of recent entries (0 for all)
 * @return array Weight entries
 */
function getWeightData($userId, $limit = 0) {
    $filePath = WEIGHT_PATH . $userId . '_weight.json';
    $data = readJsonFile($filePath);
    
    if (!$data || !isset($data['entries'])) {
        return [];
    }
    
    // Sort by date (newest first)
    usort($data['entries'], function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    if ($limit > 0) {
        return array_slice($data['entries'], 0, $limit);
    }
    
    return $data['entries'];
}

/**
 * Add weight entry
 * @param string $userId User ID
 * @param float $weight Weight value
 * @param string $unit Weight unit
 * @param string $date Date (optional, defaults to today)
 * @param string $notes Notes (optional)
 * @return bool True on success
 */
function addWeightEntry($userId, $weight, $unit = 'lbs', $date = null, $notes = '') {
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    $filePath = WEIGHT_PATH . $userId . '_weight.json';
    $data = readJsonFile($filePath);
    
    if (!$data) {
        $data = ['entries' => []];
    }
    
    $entry = [
        'id' => generateRandomString(8),
        'weight' => (float)$weight,
        'unit' => $unit,
        'date' => $date,
        'notes' => $notes,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $data['entries'][] = $entry;
    
    return writeJsonFile($filePath, $data);
}

/**
 * Get exercise data for user
 * @param string $userId User ID
 * @param int $limit Number of recent entries (0 for all)
 * @return array Exercise entries
 */
function getExerciseData($userId, $limit = 0) {
    $filePath = EXERCISE_PATH . $userId . '_exercise.json';
    $data = readJsonFile($filePath);
    
    if (!$data || !isset($data['entries'])) {
        return [];
    }
    
    // Sort by date (newest first)
    usort($data['entries'], function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    if ($limit > 0) {
        return array_slice($data['entries'], 0, $limit);
    }
    
    return $data['entries'];
}

/**
 * Add exercise entry
 * @param string $userId User ID
 * @param string $exercise Exercise name
 * @param int $duration Duration in minutes
 * @param int $calories Calories burned (optional)
 * @param string $date Date (optional, defaults to today)
 * @param string $notes Notes (optional)
 * @return bool True on success
 */
function addExerciseEntry($userId, $exercise, $duration, $calories = 0, $date = null, $notes = '') {
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    $filePath = EXERCISE_PATH . $userId . '_exercise.json';
    $data = readJsonFile($filePath);
    
    if (!$data) {
        $data = ['entries' => []];
    }
    
    $entry = [
        'id' => generateRandomString(8),
        'exercise' => $exercise,
        'duration' => (int)$duration,
        'calories' => (int)$calories,
        'date' => $date,
        'notes' => $notes,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $data['entries'][] = $entry;
    
    return writeJsonFile($filePath, $data);
}

/**
 * Get food data for user
 * @param string $userId User ID
 * @param string $date Specific date (optional)
 * @param int $limit Number of recent entries (0 for all)
 * @return array Food entries
 */
function getFoodData($userId, $date = null, $limit = 0) {
    $filePath = FOOD_PATH . $userId . '_food.json';
    $data = readJsonFile($filePath);
    
    if (!$data || !isset($data['entries'])) {
        return [];
    }
    
    $entries = $data['entries'];
    
    // Filter by date if specified
    if ($date) {
        $entries = array_filter($entries, function($entry) use ($date) {
            return $entry['date'] === $date;
        });
    }
    
    // Sort by timestamp (newest first)
    usort($entries, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    if ($limit > 0) {
        return array_slice($entries, 0, $limit);
    }
    
    return $entries;
}

/**
 * Add food entry
 * @param string $userId User ID
 * @param string $food Food name
 * @param int $calories Calories
 * @param string $meal Meal type (breakfast, lunch, dinner, snack)
 * @param string $date Date (optional, defaults to today)
 * @param array $nutrition Nutrition info (optional)
 * @return bool True on success
 */
function addFoodEntry($userId, $food, $calories, $meal, $date = null, $nutrition = []) {
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    $filePath = FOOD_PATH . $userId . '_food.json';
    $data = readJsonFile($filePath);
    
    if (!$data) {
        $data = ['entries' => []];
    }
    
    $entry = [
        'id' => generateRandomString(8),
        'food' => $food,
        'calories' => (int)$calories,
        'meal' => $meal,
        'date' => $date,
        'nutrition' => $nutrition,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $data['entries'][] = $entry;
    
    return writeJsonFile($filePath, $data);
}

/**
 * Get recipes for user
 * @param string $userId User ID
 * @param bool $includePublic Include public recipes
 * @return array Recipe entries
 */
function getRecipes($userId, $includePublic = false) {
    $recipes = [];
    
    // Get user's personal recipes
    $filePath = RECIPES_PATH . $userId . '_recipes.json';
    $userData = readJsonFile($filePath);
    if ($userData && isset($userData['recipes'])) {
        $recipes = array_merge($recipes, $userData['recipes']);
    }
    
    // Get public recipes if requested
    if ($includePublic) {
        $publicPath = RECIPES_PATH . 'public_recipes.json';
        $publicData = readJsonFile($publicPath);
        if ($publicData && isset($publicData['recipes'])) {
            $recipes = array_merge($recipes, $publicData['recipes']);
        }
    }
    
    return $recipes;
}

/**
 * Add recipe
 * @param string $userId User ID
 * @param string $name Recipe name
 * @param array $ingredients List of ingredients
 * @param array $instructions List of instructions
 * @param array $nutrition Nutrition information
 * @param bool $isPublic Make recipe public
 * @return bool True on success
 */
function addRecipe($userId, $name, $ingredients, $instructions, $nutrition = [], $isPublic = false) {
    $filePath = RECIPES_PATH . $userId . '_recipes.json';
    $data = readJsonFile($filePath);
    
    if (!$data) {
        $data = ['recipes' => []];
    }
    
    $recipe = [
        'id' => generateRandomString(8),
        'name' => $name,
        'ingredients' => $ingredients,
        'instructions' => $instructions,
        'nutrition' => $nutrition,
        'created_by' => $userId,
        'created_at' => date('Y-m-d H:i:s'),
        'is_public' => $isPublic
    ];
    
    $data['recipes'][] = $recipe;
    
    return writeJsonFile($filePath, $data);
}

/**
 * Delete entry by ID
 * @param string $filePath Path to data file
 * @param string $entryId Entry ID to delete
 * @return bool True on success
 */
function deleteEntry($filePath, $entryId) {
    $data = readJsonFile($filePath);
    if (!$data) return false;
    
    $key = isset($data['entries']) ? 'entries' : 'recipes';
    if (!isset($data[$key])) return false;
    
    $originalCount = count($data[$key]);
    $data[$key] = array_filter($data[$key], function($entry) use ($entryId) {
        return $entry['id'] !== $entryId;
    });
    
    // Re-index array
    $data[$key] = array_values($data[$key]);
    
    if (count($data[$key]) < $originalCount) {
        return writeJsonFile($filePath, $data);
    }
    
    return false;
}
?>