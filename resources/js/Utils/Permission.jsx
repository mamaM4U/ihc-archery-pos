import { usePage } from "@inertiajs/react";

export default function hasAnyPermission(permissions, givenPermissions = null) {
    // destruct auth from usepage props if not provided
    const { auth } = usePage().props;

    // get all permissions from props auth.permissions or provided map
    let allPermissions = givenPermissions ?? auth.permissions;

    // define has permission is false
    let hasPermission = false;

    // loop permissions
    permissions.forEach(function (item) {
        // do it if permission is match with key
        if (allPermissions[item])
            // assign hasPermission to true
            hasPermission = true;
    });

    // return has permissions
    return hasPermission;
}
