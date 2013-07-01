<?php

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

?>


<html>
    <body>
        <br /><br />
        <p>
            For best results don't select any filters when searching for a specific question!
        </p>
    
        <table>
            <tr>
                <td>
                    To find a specific question, search by...<br />
                    <select>
                        <option>Select One (Optional)</option>
                        <option>Question Reference ID</option>
                        <option>Question Text</option>
                    <select>
                    
                    <input type='text' />
                </td>
            </tr>
            
            <tr>
                <td colspan='2' style='padding-top:10px;'>
                    Filter Questions...
                </td>
            </tr>
            <tr>
                <td>
                    Approved<br />
                    <select>
                        <option>Select To Apply Filter</option>
                        <option>No</option>
                        <option>Yes</option>
                        <option>Both</option>
                    </select>
                </td>
            </tr>
            <tr>
            <td>
                <input type='submit' value='Search' />
            </td>
            </tr>
        </table>
    
    </body>
</html>