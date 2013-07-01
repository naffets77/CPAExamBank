<?php

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

?>


<html>
    <body>
        <p>
            For best results don't select any filters when searching for a specific question!
        </p>
    
        <table>
            <tr>
                <td>
                    To find a specific question, search by...
                </td>
                <td>
                    <select>
                        <option>Select One (Optional)</option>
                        <option>Question Reference ID</option>
                        <option>Question Text</option>
                    <select>
                </td>
            </tr>
            
            <tr>
                <td colspan='2'>
                    Filter Questions...
                </td>
            </tr>
            <tr>
                <td>Approved</td>
                <td>
                    <select>
                        <option>Select To Apply Filter</option>
                        <option>No</option>
                        <option>Yes</option>
                        <option>Both</option>
                    </select>
                </td>
            </tr>
        </table>
    
    </body>
</html>