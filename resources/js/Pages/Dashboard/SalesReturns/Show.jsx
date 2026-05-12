import React from "react";
import SalesReturnForm from "./Form";

export default function Show({ salesReturn, transaction }) {
    return (
        <SalesReturnForm
            title={salesReturn.code}
            transaction={transaction}
            salesReturn={salesReturn}
            submitRoute={route("sales-returns.update", salesReturn.id)}
            submitMethod="patch"
            canEdit={salesReturn.status === "draft"}
            canComplete={salesReturn.status === "draft"}
            completeRoute={route("sales-returns.complete", salesReturn.id)}
        />
    );
}

Show.layout = SalesReturnForm.layout;
